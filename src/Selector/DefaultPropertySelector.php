<?php

declare(strict_types=1);

namespace JMS\Serializer\Selector;

use JMS\Serializer\Context;
use JMS\Serializer\DeserializationContext;
use JMS\Serializer\Exclusion\ExclusionStrategyInterface;
use JMS\Serializer\Exclusion\ExpressionLanguageExclusionStrategy;
use JMS\Serializer\Expression\ExpressionEvaluatorInterface;
use JMS\Serializer\Metadata\ClassMetadata;
use JMS\Serializer\Metadata\PropertyMetadata;

/**
 * @author Asmir Mustafic <goetas@gmail.com>
 */
final class DefaultPropertySelector implements PropertySelectorInterface
{
    /**
     * @var ExclusionStrategyInterface
     */
    private $exclusionStrategy;
    /**
     * @var ExpressionLanguageExclusionStrategy
     */
    private $expressionExclusionStrategy;
    /**
     * @var Context
     */
    private $context;
    /**
     * @var array
     */
    private $cache = [];

    public function __construct(
        Context $context,
        ExpressionEvaluatorInterface $evaluator = null
    ) {
        $this->exclusionStrategy = $context->getExclusionStrategy();
        $this->context = $context;
        if ($evaluator) {
            $this->expressionExclusionStrategy = new ExpressionLanguageExclusionStrategy($evaluator);
        }
    }

    /**
     * @param ClassMetadata $metadata
     * @return PropertyMetadata[]
     */
    public function select(ClassMetadata $metadata): array
    {
        if (!isset($this->cache[spl_object_hash($metadata)]) || $metadata->usingExpression !== false) {

            $values = [];
            foreach ($metadata->propertyMetadata as $propertyMetadata) {
                if ($this->context instanceof DeserializationContext && $propertyMetadata->readOnly) {
                    continue;
                }

                if ($this->exclusionStrategy->shouldSkipProperty($propertyMetadata, $this->context)) {
                    continue;
                }

                if ($this->expressionExclusionStrategy!== null && $this->expressionExclusionStrategy->shouldSkipProperty($propertyMetadata, $this->context)) {
                    continue;
                }

                $values[] = $propertyMetadata;
            }
            $this->cache[spl_object_hash($metadata)] = $values;
        }

        return $this->cache[spl_object_hash($metadata)];
    }
}

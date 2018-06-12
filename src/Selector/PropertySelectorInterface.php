<?php
declare(strict_types=1);

namespace JMS\Serializer\Selector;

use JMS\Serializer\Metadata\ClassMetadata;
use JMS\Serializer\Metadata\PropertyMetadata;

/**
 * @author Asmir Mustafic <goetas@gmail.com>
 */
interface PropertySelectorInterface
{
    /**
     * @param ClassMetadata $metadata
     * @return PropertyMetadata[]
     */
    public function select(ClassMetadata $metadata): array;
}

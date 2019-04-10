<?php

declare(strict_types=1);

namespace JMS\Serializer\Exclusion;

use JMS\Serializer\Context;
use JMS\Serializer\Exception\RuntimeException;
use JMS\Serializer\Metadata\ClassMetadata;
use JMS\Serializer\Metadata\PropertyMetadata;

final class GroupsExclusionStrategy implements ExclusionStrategyInterface
{
    public const DEFAULT_GROUP = 'Default';

    /**
     * @var array
     */
    private $groups = [];

    private $fallback = null;

    /**
     * @var bool
     */
    private $nestedGroups = false;

    public function __construct(array $groups)
    {
        if (empty($groups)) {
            $groups = [self::DEFAULT_GROUP];
        }

        foreach ($groups as $group) {
            if (is_array($group)) {
                $this->nestedGroups = true;
                break;
            }
        }

        if ($this->nestedGroups) {
            if (array_key_exists('__fallback', $groups)) {
                $this->fallback = $groups['__fallback'];
                unset($groups['__fallback']);
            }

            $this->groups = $groups;
        } else {
            foreach ($groups as $group) {
                $this->groups[$group] = true;
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function shouldSkipClass(ClassMetadata $metadata, Context $navigatorContext): bool
    {
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function shouldSkipProperty(PropertyMetadata $property, Context $navigatorContext): bool
    {
        if ($this->nestedGroups) {
            $groups = $this->getGroupsFor($navigatorContext);

            if (!$property->groups) {
                return !in_array(self::DEFAULT_GROUP, $groups);
            }

            return $this->shouldSkipUsingGroups($property, $groups);
        } else {
            if (!$property->groups) {
                return !isset($this->groups[self::DEFAULT_GROUP]);
            }

            foreach ($property->groups as $group) {
                if (isset($this->groups[$group])) {
                    return false;
                }
            }
            return true;
        }
    }

    private function shouldSkipUsingGroups(PropertyMetadata $property, array $groups): bool
    {
        foreach ($property->groups as $group) {
            if (in_array($group, $groups)) {
                return false;
            }
        }

        return true;
    }

    public function getGroupsFor(Context $navigatorContext): array
    {
        if (!$this->nestedGroups) {
            return array_keys($this->groups);
        }

        $paths = $navigatorContext->getCurrentPath();
        $groups = $this->groups;
        $single = array_filter($groups, 'is_scalar');

        foreach ($paths as $index => $path) {
            if (!array_key_exists($path, $groups)) {
                if (!empty($this->fallback)) {
                    $groups = $this->fallback;
                } elseif ($index === 0) {
                    $groups = $single;
                }

                break;
            }

            if (!is_array($groups[$path])) {
                throw new RuntimeException(sprintf('The group value for the property path "%s" should be an array, "%s" given', $index, gettype($groups[$path])));
            }
            $groups = $groups[$path];

            $newSingle = array_filter($groups, 'is_scalar');
            if (!count($newSingle)) {
                $groups = $single + $groups;
            } else {
                $single = $newSingle;
            }
        }

        return $groups;
    }
}

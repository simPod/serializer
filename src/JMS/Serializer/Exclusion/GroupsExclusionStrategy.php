<?php

namespace JMS\Serializer\Exclusion;

use JMS\Serializer\Context;
use JMS\Serializer\Metadata\ClassMetadata;
use JMS\Serializer\Metadata\PropertyMetadata;

class GroupsExclusionStrategy implements ExclusionStrategyInterface
{
    const DEFAULT_GROUP = 'Default';

    private $groups = array();

    private $fallback = false;

    private $nestedGroups = false;

    public function __construct(array $groups, $fallback = array(self::DEFAULT_GROUP))
    {
        if (empty($groups)) {
            $groups = array(self::DEFAULT_GROUP);
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
            } else {
                $this->fallback = $fallback;
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
    public function shouldSkipClass(ClassMetadata $metadata, Context $navigatorContext)
    {
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function shouldSkipProperty(PropertyMetadata $property, Context $navigatorContext)
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

    private function shouldSkipUsingGroups(PropertyMetadata $property, $groups)
    {
        foreach ($property->groups as $group) {
            if (in_array($group, $groups)) {
                return false;
            }
        }

        return true;
    }

    public function getGroupsFor(Context $navigatorContext)
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
                }

                break;
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

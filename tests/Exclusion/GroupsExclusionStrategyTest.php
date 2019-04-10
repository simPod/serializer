<?php

declare(strict_types=1);

namespace JMS\Serializer\Tests\Exclusion;

use JMS\Serializer\Exclusion\GroupsExclusionStrategy;
use JMS\Serializer\Metadata\StaticPropertyMetadata;
use JMS\Serializer\SerializationContext;
use PHPUnit\Framework\TestCase;

class GroupsExclusionStrategyTest extends TestCase
{
    /**
     * @param array $propertyGroups
     * @param array $groups
     * @param bool $exclude
     *
     * @dataProvider getExclusionRules
     */
    public function testUninitializedContextIsWorking(array $propertyGroups, array $groups, $exclude)
    {
        $metadata = new StaticPropertyMetadata('stdClass', 'prop', 'propVal');
        $metadata->groups = $propertyGroups;

        $strat = new GroupsExclusionStrategy($groups);
        self::assertEquals($strat->shouldSkipProperty($metadata, SerializationContext::create()), $exclude);
    }

    /**
     * @dataProvider getGroupsFor
     * @param $groups
     * @param $propsVisited
     * @param $resultingGroups
     */
    public function testGroupsFor($groups, $propsVisited, $resultingGroups)
    {
        $exclusion = new GroupsExclusionStrategy($groups);
        $context = SerializationContext::create();

        foreach ($propsVisited as $prop) {
            $metadata = new StaticPropertyMetadata('stdClass', $prop, 'propVal');
            $context->pushPropertyMetadata($metadata);
        }

        $groupsFor = $exclusion->getGroupsFor($context);
        $this->assertEquals($groupsFor, $resultingGroups);
    }

    public function getGroupsFor()
    {
        return [
            [['foo'], ['prop'], ['foo']],
            [[], ['prop'], ['Default']],

            [['foo', 'prop' => ['bar']], ['prop'], ['bar']],
            [['foo', 'prop' => ['bar']], ['prop2'], ['foo']],

            [['__fallback' => ['Default'], 'foo', 'prop' => ['bar']], ['prop2'], ['Default']], // v1 emulation

            [['foo', 'prop' => ['bar']], ['prop', 'prop2'], ['bar']],
            [['__fallback' => ['Default'],'foo', 'prop' => ['bar']], ['prop', 'prop2'], ['Default']], // v1 emulation

            [['foo', 'prop' => ['xx', 'prop2' => ['def'], 'prop3' => ['def']]], ['prop', 'prop2', 'propB'], ['def']],
            [['__fallback' => ['Default'], 'foo', 'prop' => ['xx', 'prop2' => ['def'], 'prop3' => ['def']]], ['prop', 'prop2', 'propB'], ['Default']], // v1 emulation
        ];
    }

    public function getExclusionRules()
    {
        return [
            [['foo'], ['foo'], false],
            [['foo'], [], true],
            [[], ['foo'], true],
            [['foo'], ['bar'], true],
            [['bar'], ['foo'], true],

            [['foo', GroupsExclusionStrategy::DEFAULT_GROUP], [], false],
            [['foo', 'bar'], [], true],
            [['foo', 'bar'], [GroupsExclusionStrategy::DEFAULT_GROUP], true],
            [['foo', 'bar'], ['foo'], false],

            [['foo', GroupsExclusionStrategy::DEFAULT_GROUP], ['test'], true],
            [['foo', GroupsExclusionStrategy::DEFAULT_GROUP, 'test'], ['test'], false],

            [['foo'], [GroupsExclusionStrategy::DEFAULT_GROUP], true],
            [[GroupsExclusionStrategy::DEFAULT_GROUP], [], false],
            [[], [GroupsExclusionStrategy::DEFAULT_GROUP], false],
            [[GroupsExclusionStrategy::DEFAULT_GROUP], [GroupsExclusionStrategy::DEFAULT_GROUP], false],
            [[GroupsExclusionStrategy::DEFAULT_GROUP, 'foo'], [GroupsExclusionStrategy::DEFAULT_GROUP], false],
            [[GroupsExclusionStrategy::DEFAULT_GROUP], [GroupsExclusionStrategy::DEFAULT_GROUP, 'foo'], false],
            [['foo'], [GroupsExclusionStrategy::DEFAULT_GROUP, 'foo'], false],
        ];
    }
}

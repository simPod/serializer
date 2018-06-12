<?php

namespace JMS\Serializer\GraphNavigator\Factory;

use JMS\Serializer\Context;
use JMS\Serializer\GraphNavigatorInterface;

interface GraphNavigatorFactoryInterface
{
    public function getGraphNavigator(Context $context): GraphNavigatorInterface;
}

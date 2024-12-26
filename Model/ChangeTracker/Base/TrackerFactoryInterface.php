<?php

namespace Ess\M2ePro\Model\ChangeTracker\Base;

use Ess\M2ePro\Model\ChangeTracker\TrackerConfiguration;

interface TrackerFactoryInterface
{
    public function createByConfiguration(TrackerConfiguration $trackerConfiguration): TrackerInterface;
}

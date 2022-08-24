<?php

namespace Ess\M2ePro\Model\ChangeTracker\Base;

/**
 * Query factory interface
 */
interface TrackerFactoryInterface
{
    /**
     * @param string $channel
     *
     * @return \Ess\M2ePro\Model\ChangeTracker\Base\TrackerInterface
     * @throws \RuntimeException
     */
    public function create(string $channel): TrackerInterface;
}

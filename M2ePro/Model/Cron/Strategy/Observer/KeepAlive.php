<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Strategy\Observer;

use Magento\Framework\Event\Observer;

/**
 * Class \Ess\M2ePro\Model\Cron\Strategy\Observer\KeepAlive
 */
class KeepAlive implements \Magento\Framework\Event\ObserverInterface
{
    const ACTIVATE_INTERVAL = 30;

    private $isEnabled = false;

    /** @var \Ess\M2ePro\Model\Lock\Item\Manager */
    private $lockItemManager = null;

    private $circleStartTime = null;

    //########################################

    public function enable()
    {
        $this->isEnabled       = true;
        $this->circleStartTime = null;

        return $this;
    }

    public function disable()
    {
        $this->isEnabled       = false;
        $this->circleStartTime = null;

        return $this;
    }

    //########################################

    public function setLockItemManager(\Ess\M2ePro\Model\Lock\Item\Manager $lockItemManager)
    {
        $this->lockItemManager = $lockItemManager;
        return $this;
    }

    //########################################

    public function execute(Observer $observer)
    {
        if (!$this->isEnabled) {
            return;
        }

        if ($this->lockItemManager === null) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Lock Item Manager was not set.');
        }

        if ($observer->getEvent()->getData('object') &&
            ($observer->getEvent()->getData('object') instanceof \Ess\M2ePro\Model\Lock\Item)
        ) {
            return;
        }

        if ($observer->getEvent()->getData('collection') &&
            ($observer->getEvent()->getData('collection') instanceof
                \Ess\M2ePro\Model\ResourceModel\Lock\Item\Collection
            )
        ) {
            return;
        }

        if ($this->circleStartTime === null) {
            $this->circleStartTime = time();
            return;
        }

        if ($this->circleStartTime + self::ACTIVATE_INTERVAL > time()) {
            return;
        }

        $this->lockItemManager->activate();

        $this->circleStartTime = time();
    }

    //########################################
}

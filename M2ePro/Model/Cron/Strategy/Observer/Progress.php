<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Strategy\Observer;

use Magento\Framework\Event\Observer;

/**
 * Class \Ess\M2ePro\Model\Cron\Strategy\Observer\Progress
 */
class Progress implements \Magento\Framework\Event\ObserverInterface
{
    private $isEnabled = false;

    /** @var \Ess\M2ePro\Model\Lock\Item\Manager */
    private $lockItemManager = null;

    /** @var \Ess\M2ePro\Model\Factory */
    private $modelFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\Factory $modelFactory
    ) {
        $this->modelFactory = $modelFactory;
    }

    //########################################

    public function enable()
    {
        $this->isEnabled = true;
        return $this;
    }

    public function disable()
    {
        $this->isEnabled = false;
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

        $eventName    = $observer->getEvent()->getName();
        $progressNick = $observer->getEvent()->getProgressNick();

        $progress = $this->modelFactory->getObject(
            'Lock_Item_Progress',
            [
                'lockItemManager' => $this->lockItemManager,
                'progressNick'    => $progressNick
            ]
        );

        if ($eventName == \Ess\M2ePro\Model\Cron\Strategy\AbstractModel::PROGRESS_START_EVENT_NAME) {
            $progress->start();
            return;
        }

        if ($eventName == \Ess\M2ePro\Model\Cron\Strategy\AbstractModel::PROGRESS_SET_PERCENTAGE_EVENT_NAME) {
            $percentage = $observer->getEvent()->getData('percentage');
            $progress->setPercentage($percentage);
            return;
        }

        if ($eventName == \Ess\M2ePro\Model\Cron\Strategy\AbstractModel::PROGRESS_SET_DETAILS_EVENT_NAME) {
            $args = [
                'percentage' => $observer->getEvent()->getData('percentage'),
                'total'      => $observer->getEvent()->getData('total')
            ];
            $progress->setDetails($args);
            return;
        }

        if ($eventName == \Ess\M2ePro\Model\Cron\Strategy\AbstractModel::PROGRESS_STOP_EVENT_NAME) {
            $progress->stop();
            return;
        }
    }

    //########################################
}

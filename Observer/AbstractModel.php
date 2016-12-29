<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Observer;

abstract class AbstractModel implements \Magento\Framework\Event\ObserverInterface
{
    protected $helperFactory;
    protected $activeRecordFactory;
    protected $modelFactory;

    /**
     * @var null|\Magento\Framework\Event\Observer
     */
    private $eventObserver = NULL;

    //########################################

    public function __construct(
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    )
    {
        $this->helperFactory = $helperFactory;
        $this->activeRecordFactory = $activeRecordFactory;
        $this->modelFactory = $modelFactory;
    }

    //########################################

    public function getHelper($helperName)
    {
        return $this->helperFactory->getObject($helperName);
    }

    //########################################

    public function canProcess()
    {
        return true;
    }

    public function execute(\Magento\Framework\Event\Observer $eventObserver)
    {
        if ($this->helperFactory->getObject('Module\Maintenance\General')->isEnabled() ||
            $this->helperFactory->getObject('Module')->isDisabled() ||
            !$this->helperFactory->getObject('Module')->isReadyToWork()) {

            return;
        }

        try {

            $this->setEventObserver($eventObserver);

            if (!$this->canProcess()) {
                return;
            }

            $this->beforeProcess();
            $this->process();
            $this->afterProcess();

        } catch (\Exception $exception) {
            $this->helperFactory->getObject('Module\Exception')->process($exception);
        }
    }

    abstract public function process();

    //########################################

    public function beforeProcess() {}
    public function afterProcess() {}

    //########################################

    /**
     * @param \Magento\Framework\Event\Observer $eventObserver
     */
    public function setEventObserver(\Magento\Framework\Event\Observer $eventObserver)
    {
        $this->eventObserver = $eventObserver;
    }

    /**
     * @return \Magento\Framework\Event\Observer
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function getEventObserver()
    {
        if (!($this->eventObserver instanceof \Magento\Framework\Event\Observer)) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Property "eventObserver" should be set first.');
        }

        return $this->eventObserver;
    }

    //########################################

    /**
     * @return \Magento\Framework\Event
     */
    protected function getEvent()
    {
        return $this->getEventObserver()->getEvent();
    }

    //########################################
}
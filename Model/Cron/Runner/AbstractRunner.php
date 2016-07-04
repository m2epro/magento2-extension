<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Runner;

use Ess\M2ePro\Model\AbstractModel;

abstract class AbstractRunner extends AbstractModel
{
    const MAX_MEMORY_LIMIT = 1024;

    //########################################

    protected $storeManager;

    protected $magentoConfig;

    protected $activeRecordFactory;

    private $previousStoreId = NULL;

    /** @var \Ess\M2ePro\Model\OperationHistory $operationHistory */
    private $operationHistory = NULL;

    //########################################

    abstract protected function getNick();

    abstract protected function getInitiator();

    //########################################

    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Config\Model\Config $magentoConfig,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    ) {
        $this->storeManager  = $storeManager;
        $this->magentoConfig = $magentoConfig;
        $this->activeRecordFactory = $activeRecordFactory;
        parent::__construct($helperFactory, $modelFactory);
    }

    //########################################

    public function process()
    {
        $this->initialize();
        $this->updateLastAccess();

        if (!$this->isPossibleToRun()) {
            $this->deInitialize();
            return true;
        }

        $this->updateLastRun();
        $this->beforeStart();

        try {

            /** @var \Ess\M2ePro\Model\Cron\Strategy\AbstractStrategy $strategyObject */
            $strategyObject = $this->getStrategyObject();

            $strategyObject->setInitiator($this->getInitiator());
            $strategyObject->setParentOperationHistory($this->getOperationHistory());

            $result = $strategyObject->process();

        } catch (\Exception $exception) {

            $result = false;

            $this->getOperationHistory()->addContentData('exception', array(
                'message' => $exception->getMessage(),
                'file'    => $exception->getFile(),
                'line'    => $exception->getLine(),
                'trace'   => $exception->getTraceAsString(),
            ));

            $this->getHelper('Module\Exception')->process($exception);
        }

        $this->afterEnd();
        $this->deInitialize();

        return $result;
    }

    /**
     * @return \Ess\M2ePro\Model\Cron\Strategy\AbstractStrategy
     */
    abstract protected function getStrategyObject();

    //########################################

    protected function initialize()
    {
        $this->previousStoreId = $this->storeManager->getStore()->getId();

        $this->storeManager->setCurrentStore(\Magento\Store\Model\Store::DEFAULT_STORE_ID);

        $this->getHelper('Client')->setMemoryLimit(self::MAX_MEMORY_LIMIT);
        $this->getHelper('Module\Exception')->setFatalErrorHandler();
    }

    protected function deInitialize()
    {
        if (!is_null($this->previousStoreId)) {
            $this->storeManager->setCurrentStore($this->previousStoreId);
            $this->previousStoreId = NULL;
        }
    }

    //########################################

    protected function updateLastAccess()
    {
        $currentDateTime = $this->getHelper('Data')->getCurrentGmtDate();
        $this->getHelper('Module\Cron')->setLastAccess($currentDateTime);
    }

    protected function isPossibleToRun()
    {
        if ($this->getHelper('Module\Maintenance\Setup')->isEnabled()) {
            return false;
        }

        if (!$this->getHelper('Module')->isReadyToWork()) {
            return false;
        }

        if ($this->getNick() != $this->getHelper('Module\Cron')->getRunner()) {
            return false;
        }

        if (!$this->getHelper('Module\Cron')->isModeEnabled()) {
            return false;
        }

        return true;
    }

    protected function updateLastRun()
    {
        $currentDateTime = $this->getHelper('Data')->getCurrentGmtDate();
        $this->getHelper('Module\Cron')->setLastRun($currentDateTime);
    }

    // ---------------------------------------

    protected function beforeStart()
    {
        $this->getOperationHistory()->start('cron_runner', null, $this->getInitiator());
        $this->getOperationHistory()->makeShutdownFunction();
    }

    protected function afterEnd()
    {
        $this->getOperationHistory()->stop();
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\OperationHistory
     */
    protected function getOperationHistory()
    {
        if (!is_null($this->operationHistory)) {
            return $this->operationHistory;
        }

        return $this->operationHistory = $this->activeRecordFactory->getObject('OperationHistory');
    }

    //########################################
}
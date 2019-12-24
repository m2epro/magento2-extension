<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Runner;

/**
 * Class \Ess\M2ePro\Model\Cron\Runner\AbstractModel
 */
abstract class AbstractModel extends \Ess\M2ePro\Model\AbstractModel
{
    const MAX_INACTIVE_TIME = 300;
    const MAX_MEMORY_LIMIT  = 2048;

    //########################################

    protected $storeManager;

    protected $magentoConfig;

    protected $activeRecordFactory;

    private $previousStoreId = null;

    /** @var \Ess\M2ePro\Model\OperationHistory $operationHistory */
    private $operationHistory = null;

    /** @var \Ess\M2ePro\Model\Setup\PublicVersionsChecker $publicVersionsChecker */
    private $publicVersionsChecker = null;

    //########################################

    abstract public function getNick();

    abstract public function getInitiator();

    //########################################

    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Config\Model\Config $magentoConfig,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\Setup\PublicVersionsChecker $publicVersionsChecker,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    ) {
        $this->storeManager  = $storeManager;
        $this->magentoConfig = $magentoConfig;
        $this->activeRecordFactory = $activeRecordFactory;
        $this->publicVersionsChecker = $publicVersionsChecker;
        parent::__construct($helperFactory, $modelFactory);
    }

    //########################################

    public function process()
    {
        if (!$this->helperFactory->getObject('Magento')->isInstalled()) {
            return false;
        }

        if ($this->getHelper('Module\Maintenance')->isEnabled()) {
            return false;
        }

        if ($this->isDisabled()) {
            return false;
        }

        $runnerSwitcher = $this->modelFactory->getObject('Cron_Runner_Switcher');
        $runnerSwitcher->check($this);

        $transactionalManager = $this->modelFactory->getObject('Lock_Transactional_Manager');
        $transactionalManager->setNick('cron_runner');

        $transactionalManager->lock();

        $this->initialize();
        $this->updateLastAccess();

        if (!$this->isPossibleToRun()) {
            $this->deInitialize();
            $transactionalManager->unlock();

            return true;
        }

        $this->publicVersionsChecker->doCheck();

        $this->updateLastRun();
        $this->beforeStart();

        $transactionalManager->unlock();

        try {

            /** @var \Ess\M2ePro\Model\Cron\Strategy\AbstractModel $strategyObject */
            $strategyObject = $this->getStrategyObject();

            $strategyObject->setInitiator($this->getInitiator());
            $strategyObject->setParentOperationHistory($this->getOperationHistory());

            $result = $strategyObject->process();
        } catch (\Exception $exception) {
            $result = false;

            $this->getOperationHistory()->addContentData('exception', [
                'message' => $exception->getMessage(),
                'file'    => $exception->getFile(),
                'line'    => $exception->getLine(),
                'trace'   => $exception->getTraceAsString(),
            ]);

            $this->getHelper('Module\Exception')->process($exception);
        }

        $this->afterEnd();
        $this->deInitialize();

        return $result;
    }

    /**
     * @return \Ess\M2ePro\Model\Cron\Strategy\AbstractModel
     */
    abstract protected function getStrategyObject();

    //########################################

    protected function isDisabled()
    {
        if ($this->getHelper('Module')->getConfig()->getGroupValue('/cron/'.$this->getNick().'/', 'disabled')) {
            return true;
        }

        return false;
    }

    protected function initialize()
    {
        $this->previousStoreId = $this->storeManager->getStore()->getId();

        $this->storeManager->setCurrentStore(\Magento\Store\Model\Store::DEFAULT_STORE_ID);

        $this->getHelper('Client')->setMemoryLimit(self::MAX_MEMORY_LIMIT);
        $this->getHelper('Module\Exception')->setFatalErrorHandler();
    }

    protected function deInitialize()
    {
        if ($this->previousStoreId !== null) {
            $this->storeManager->setCurrentStore($this->previousStoreId);
            $this->previousStoreId = null;
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
        if ($this->getHelper('Module')->isDisabled()) {
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
        $this->getOperationHistory()
            ->start('cron_runner', null, $this->getInitiator(), $this->getOperationHistoryData());
        $this->getOperationHistory()->makeShutdownFunction();
    }

    protected function afterEnd()
    {
        $this->getOperationHistory()->stop();
    }

    // ---------------------------------------

    protected function getOperationHistoryData()
    {
        return ['runner' => $this->getNick()];
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\OperationHistory
     */
    public function getOperationHistory()
    {
        if ($this->operationHistory !== null) {
            return $this->operationHistory;
        }

        return $this->operationHistory = $this->activeRecordFactory->getObject('OperationHistory');
    }

    //########################################
}

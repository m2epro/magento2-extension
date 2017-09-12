<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Runner;

abstract class AbstractModel extends \Ess\M2ePro\Model\AbstractModel
{
    const MAX_MEMORY_LIMIT = 1024;

    //########################################

    protected $storeManager;

    protected $magentoConfig;

    protected $activeRecordFactory;

    private $previousStoreId = NULL;

    /** @var \Ess\M2ePro\Model\OperationHistory $operationHistory */
    private $operationHistory = NULL;

    /** @var \Ess\M2ePro\Model\Setup\PublicVersionsChecker $publicVersionsChecker */
    private $publicVersionsChecker = NULL;

    //########################################

    abstract protected function getNick();

    abstract protected function getInitiator();

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
        $transactionalManager = $this->modelFactory->getObject('Lock\Transactional\Manager');
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
     * @return \Ess\M2ePro\Model\Cron\Strategy\AbstractModel
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
        if ($this->getHelper('Module\Maintenance\General')->isEnabled()) {
            return false;
        }

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
        $this->getOperationHistory()->start('cron_runner',null,$this->getInitiator(),$this->getOperationHistoryData());
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
        if (!is_null($this->operationHistory)) {
            return $this->operationHistory;
        }

        return $this->operationHistory = $this->activeRecordFactory->getObject('OperationHistory');
    }

    //########################################
}
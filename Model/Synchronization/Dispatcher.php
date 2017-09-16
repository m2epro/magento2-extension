<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Synchronization;

class Dispatcher extends \Ess\M2ePro\Model\AbstractModel
{
    const MAX_MEMORY_LIMIT = 512;

    private $resourceConnection;
    private $activeRecordFactory;
    private $synchConfig;
    private $eventManager;

    private $allowedComponents = array();
    private $allowedTasksTypes = array();

    private $lockItem = NULL;
    private $operationHistory = NULL;

    private $parentLockItem = NULL;
    private $parentOperationHistory = NULL;

    private $log = NULL;
    private $params = array();
    private $initiator = \Ess\M2ePro\Helper\Data::INITIATOR_UNKNOWN;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\Config\Manager\Synchronization $synchConfig,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    )
    {
        $this->synchConfig = $synchConfig;
        $this->resourceConnection = $resourceConnection;
        $this->activeRecordFactory = $activeRecordFactory;
        $this->eventManager = $eventManager;
        parent::__construct($helperFactory, $modelFactory);
    }

    //########################################

    public function process()
    {
        $this->initialize();
        $this->updateLastAccess();

        if (!$this->isPossibleToRun()) {
            return true;
        }

        $this->updateLastRun();
        $this->beforeStart();

        $result = true;

        try {

            // global tasks
            $result = !$this->processGlobal() ? false : $result;

            // components tasks
            $result = !$this->processComponent(\Ess\M2ePro\Helper\Component\Ebay::NICK) ? false : $result;
            $result = !$this->processComponent(\Ess\M2ePro\Helper\Component\Amazon::NICK) ? false : $result;
            //$result = !$this->processComponent(\Ess\M2ePro\Helper\Component\Buy::NICK) ? false : $result;

        } catch (\Exception $exception) {

            $result = false;

            $this->getHelper('Module\Exception')->process($exception);

            $this->getOperationHistory()->addContentData('exception', array(
                'message' => $exception->getMessage(),
                'file'    => $exception->getFile(),
                'line'    => $exception->getLine(),
                'trace'   => $exception->getTraceAsString(),
            ));

            $this->getLog()->addMessage(
                $this->getHelper('Module\Translation')->__($exception->getMessage()),
                \Ess\M2ePro\Model\Log\AbstractModel::TYPE_ERROR,
                \Ess\M2ePro\Model\Log\AbstractModel::PRIORITY_HIGH
            );
        }

        $this->afterEnd();

        return $result;
    }

    // ---------------------------------------

    protected function processGlobal()
    {
        return $this->processTask('Synchronization\GlobalTask\Launcher');
    }

    protected function processComponent($component)
    {
        if (!in_array($component,$this->getAllowedComponents())) {
            return false;
        }

        return $this->processTask(ucfirst($component).'\Synchronization\Launcher');
    }

    protected function processTask($taskPath)
    {
        $result = $this->makeTask($taskPath)->process();
        return is_null($result) || $result;
    }

    protected function makeTask($taskPath)
    {
        /** @var $task \Ess\M2ePro\Model\Synchronization\AbstractTask **/
        $task = $this->modelFactory->getObject($taskPath);

        $task->setParentLockItem($this->getLockItem());
        $task->setParentOperationHistory($this->getOperationHistory());

        $task->setAllowedTasksTypes($this->getAllowedTasksTypes());

        $task->setLog($this->getLog());
        $task->setInitiator($this->getInitiator());
        $task->setParams($this->getParams());

        return $task;
    }

    //########################################

    /**
     * @param array $components
     */
    public function setAllowedComponents(array $components)
    {
        $this->allowedComponents = $components;
    }

    /**
     * @return array
     */
    public function getAllowedComponents()
    {
        return $this->allowedComponents;
    }

    // ---------------------------------------

    /**
     * @param array $types
     */
    public function setAllowedTasksTypes(array $types)
    {
        $this->allowedTasksTypes = $types;
    }

    /**
     * @return array
     */
    public function getAllowedTasksTypes()
    {
        return $this->allowedTasksTypes;
    }

    // ---------------------------------------

    /**
     * @param array $params
     */
    public function setParams(array $params)
    {
        $this->params = $params;
    }

    /**
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }

    // ---------------------------------------

    /**
     * @param int $value
     */
    public function setInitiator($value)
    {
        $this->initiator = (int)$value;
    }

    /**
     * @return int
     */
    public function getInitiator()
    {
        return $this->initiator;
    }

    // ---------------------------------------

    /**
     * @param \Ess\M2ePro\Model\Lock\Item\Manager $object
     */
    public function setParentLockItem(\Ess\M2ePro\Model\Lock\Item\Manager $object)
    {
        $this->parentLockItem = $object;
    }

    /**
     * @return \Ess\M2ePro\Model\Lock\Item\Manager
     */
    public function getParentLockItem()
    {
        return $this->parentLockItem;
    }

    // ---------------------------------------

    /**
     * @param \Ess\M2ePro\Model\OperationHistory $object
     */
    public function setParentOperationHistory(\Ess\M2ePro\Model\OperationHistory $object)
    {
        $this->parentOperationHistory = $object;
    }

    /**
     * @return \Ess\M2ePro\Model\OperationHistory
     */
    public function getParentOperationHistory()
    {
        return $this->parentOperationHistory;
    }

    //########################################

    protected function initialize()
    {
        $this->getHelper('Client')->setMemoryLimit(self::MAX_MEMORY_LIMIT);
        $this->getHelper('Module\Exception')->setFatalErrorHandler();
    }

    protected function updateLastAccess()
    {
        $currentDateTime = $this->getHelper('Data')->getCurrentGmtDate();
        $this->setConfigValue(NULL,'last_access',$currentDateTime);
    }

    protected function isPossibleToRun()
    {
        return (bool)(int)$this->getConfigValue(NULL,'mode') &&
               !$this->getLockItem()->isExist();
    }

    protected function updateLastRun()
    {
        $currentDateTime = $this->getHelper('Data')->getCurrentGmtDate();
        $this->setConfigValue(NULL,'last_run',$currentDateTime);
    }

    // ---------------------------------------

    protected function beforeStart()
    {
        $lockItemParentId = $this->getParentLockItem() ? $this->getParentLockItem()->getRealId() : NULL;
        $this->getLockItem()->create($lockItemParentId);
        $this->getLockItem()->makeShutdownFunction();

        $this->getOperationHistory()->cleanOldData();

        $operationHistoryParentId = $this->getParentOperationHistory() ?
                $this->getParentOperationHistory()->getObject()->getId() : NULL;
        $this->getOperationHistory()->start('synchronization',
                                            $operationHistoryParentId,
                                            $this->getInitiator());
        $this->getOperationHistory()->makeShutdownFunction();

        $this->getLog()->setOperationHistoryId($this->getOperationHistory()->getObject()->getId());

        if (in_array(\Ess\M2ePro\Model\Synchronization\Task\AbstractComponent::ORDERS,
            $this->getAllowedTasksTypes())) {
            $this->eventManager->dispatch('ess_synchronization_before_start', array());
        }

        if (in_array(\Ess\M2ePro\Model\Synchronization\Task\AbstractComponent::TEMPLATES,
            $this->getAllowedTasksTypes())) {
            $this->clearOutdatedProductChanges();
        }
    }

    protected function afterEnd()
    {
        if (in_array(\Ess\M2ePro\Model\Synchronization\Task\AbstractComponent::ORDERS,
            $this->getAllowedTasksTypes())) {
            $this->eventManager->dispatch('ess_synchronization_after_end', array());
        }

        if (in_array(\Ess\M2ePro\Model\Synchronization\Task\AbstractComponent::TEMPLATES,
            $this->getAllowedTasksTypes())) {
            $this->clearProcessedProductChanges();
        }

        $this->getOperationHistory()->stop();
        $this->getLockItem()->remove();
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Synchronization\Lock\Item\Manager
     */
    protected function getLockItem()
    {
        if (is_null($this->lockItem)) {
            $this->lockItem = $this->modelFactory->getObject('Synchronization\Lock\Item\Manager');
        }
        return $this->lockItem;
    }

    /**
     * @return \Ess\M2ePro\Model\Synchronization\OperationHistory
     */
    public function getOperationHistory()
    {
        if (is_null($this->operationHistory)) {
            $this->operationHistory = $this->activeRecordFactory->getObject('Synchronization\OperationHistory');
        }
        return $this->operationHistory;
    }

    /**
     * @return \Ess\M2ePro\Model\Synchronization\Log
     */
    protected function getLog()
    {
        if (is_null($this->log)) {
            $this->log = $this->activeRecordFactory->getObject('Synchronization\Log');
            $this->log->setInitiator($this->getInitiator());
            $this->log->setSynchronizationTask(\Ess\M2ePro\Model\Synchronization\Log::TASK_UNKNOWN);
        }
        return $this->log;
    }

    // ---------------------------------------

    protected function clearOutdatedProductChanges()
    {
        $connWrite = $this->resourceConnection->getConnection();

        $tempDate = new \DateTime('now', new \DateTimeZone('UTC'));
        $tempDate->modify('-'.$this->getConfigValue('/settings/product_change/', 'max_lifetime').' seconds');
        $tempDate = $this->getHelper('Data')->getDate($tempDate->format('U'));

        $connWrite->delete(
            $this->resourceConnection->getTableName('m2epro_product_change'),
            array(
                'update_date <= (?)' => $tempDate
            )
        );
    }

    protected function clearProcessedProductChanges()
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\ProductChange\Collection $productChangeCollection */
        $productChangeCollection = $this->activeRecordFactory->getObject('ProductChange')->getCollection();
        $productChangeCollection->setPageSize(
            (int)$this->getConfigValue('/settings/product_change/', 'max_count_per_one_time')
        );
        $productChangeCollection->setOrder('id', \Magento\Framework\Data\Collection::SORT_ORDER_ASC);

        $connWrite = $this->resourceConnection->getConnection();

        $connWrite->delete(
            $this->resourceConnection->getTableName('m2epro_product_change'),
            array(
                'id IN (?)' => $productChangeCollection->getColumnValues('id'),
                '(update_date <= \''.$this->getOperationHistory()->getObject()->getData('start_date').'\' OR
                  initiators NOT LIKE \'%'.\Ess\M2ePro\Model\ProductChange::INITIATOR_OBSERVER.'%\')'
            )
        );
    }

    //########################################

    private function getConfig()
    {
        return $this->synchConfig;
    }

    // ---------------------------------------

    private function getConfigValue($group, $key)
    {
        return $this->getConfig()->getGroupValue($group, $key);
    }

    private function setConfigValue($group, $key, $value)
    {
        return $this->getConfig()->setGroupValue($group, $key, $value);
    }

    //########################################
}
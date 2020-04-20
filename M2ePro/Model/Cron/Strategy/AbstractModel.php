<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Strategy;

use Ess\M2ePro\Model\Cron\Task\System\HealthStatus;

/**
 * Class \Ess\M2ePro\Model\Cron\Strategy\AbstractModel
 */
abstract class AbstractModel extends \Ess\M2ePro\Model\AbstractModel
{
    const INITIALIZATION_TRANSACTIONAL_LOCK_NICK = 'cron_strategy_initialization';

    const PROGRESS_START_EVENT_NAME           = 'ess_cron_progress_start';
    const PROGRESS_SET_PERCENTAGE_EVENT_NAME  = 'ess_cron_progress_set_percentage';
    const PROGRESS_SET_DETAILS_EVENT_NAME     = 'ess_cron_progress_set_details';
    const PROGRESS_STOP_EVENT_NAME            = 'ess_cron_progress_stop';

    protected $observerKeepAlive;
    protected $observerProgress;
    protected $activeRecordFactory;

    protected $initiator = null;

    protected $allowedTasks = null;

    /**
     * @var \Ess\M2ePro\Model\Cron\OperationHistory
     */
    protected $operationHistory = null;

    /**
     * @var \Ess\M2ePro\Model\Cron\OperationHistory
     */
    protected $parentOperationHistory = null;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\Cron\Strategy\Observer\KeepAlive $observerKeepAlive,
        \Ess\M2ePro\Model\Cron\Strategy\Observer\Progress $observerProgress,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory
    ) {
        $this->observerKeepAlive = $observerKeepAlive;
        $this->observerProgress = $observerProgress;
        $this->activeRecordFactory = $activeRecordFactory;
        parent::__construct($helperFactory, $modelFactory);
    }

    //########################################

    public function setInitiator($initiator)
    {
        $this->initiator = $initiator;
        return $this;
    }

    public function getInitiator()
    {
        return $this->initiator;
    }

    // ---------------------------------------

    /**
     * @param array $tasks
     * @return $this
     */
    public function setAllowedTasks(array $tasks)
    {
        $this->allowedTasks = $tasks;
        return $this;
    }

    /**
     * @return array|null
     */
    public function getAllowedTasks()
    {
        if ($this->allowedTasks !== null) {
            return $this->allowedTasks;
        }

        return $this->allowedTasks = [
            \Ess\M2ePro\Model\Cron\Task\System\ArchiveOldOrders::NICK,
            \Ess\M2ePro\Model\Cron\Task\System\ClearOldLogs::NICK,
            \Ess\M2ePro\Model\Cron\Task\System\ConnectorCommandPending\ProcessPartial::NICK,
            \Ess\M2ePro\Model\Cron\Task\System\ConnectorCommandPending\ProcessSingle::NICK,
            \Ess\M2ePro\Model\Cron\Task\System\IssuesResolver\RemoveMissedProcessingLocks::NICK,
            \Ess\M2ePro\Model\Cron\Task\System\Processing\ProcessResult::NICK,
            \Ess\M2ePro\Model\Cron\Task\System\RequestPending\ProcessPartial::NICK,
            \Ess\M2ePro\Model\Cron\Task\System\RequestPending\ProcessSingle::NICK,
            \Ess\M2ePro\Model\Cron\Task\System\Servicing\Synchronize::NICK,
            \Ess\M2ePro\Model\Cron\Task\System\HealthStatus::NICK,
            \Ess\M2ePro\Model\Cron\Task\Magento\Product\DetectDirectlyAdded::NICK,
            \Ess\M2ePro\Model\Cron\Task\Magento\Product\DetectDirectlyDeleted::NICK,
//            \Ess\M2ePro\Model\Cron\Task\Magento\GlobalNotifications::NICK,
            \Ess\M2ePro\Model\Cron\Task\Listing\Product\InspectDirectChanges::NICK,
            \Ess\M2ePro\Model\Cron\Task\Listing\Product\AutoActions\ProcessMagentoProductWebsitesUpdates::NICK,
            \Ess\M2ePro\Model\Cron\Task\Listing\Product\StopQueue::NICK,
            \Ess\M2ePro\Model\Cron\Task\Ebay\UpdateAccountsPreferences::NICK,
            \Ess\M2ePro\Model\Cron\Task\Ebay\Template\RemoveUnused::NICK,
            \Ess\M2ePro\Model\Cron\Task\Ebay\Channel\SynchronizeChanges::NICK,
            \Ess\M2ePro\Model\Cron\Task\Ebay\Feedbacks\DownloadNew::NICK,
            \Ess\M2ePro\Model\Cron\Task\Ebay\Feedbacks\SendResponse::NICK,
            \Ess\M2ePro\Model\Cron\Task\Ebay\Listing\Other\ResolveSku::NICK,
            \Ess\M2ePro\Model\Cron\Task\Ebay\Listing\Other\Channel\SynchronizeData::NICK,
            \Ess\M2ePro\Model\Cron\Task\Ebay\Listing\Product\ProcessInstructions::NICK,
            \Ess\M2ePro\Model\Cron\Task\Ebay\Listing\Product\ProcessScheduledActions::NICK,
            \Ess\M2ePro\Model\Cron\Task\Ebay\Listing\Product\ProcessActions::NICK,
            \Ess\M2ePro\Model\Cron\Task\Ebay\Listing\Product\RemovePotentialDuplicates::NICK,
            \Ess\M2ePro\Model\Cron\Task\Ebay\Order\CreateFailed::NICK,
            \Ess\M2ePro\Model\Cron\Task\Ebay\Order\Update::NICK,
            \Ess\M2ePro\Model\Cron\Task\Ebay\Order\ReserveCancel::NICK,
            \Ess\M2ePro\Model\Cron\Task\Ebay\PickupStore\ScheduleForUpdate::NICK,
            \Ess\M2ePro\Model\Cron\Task\Ebay\PickupStore\UpdateOnChannel::NICK,
            \Ess\M2ePro\Model\Cron\Task\Amazon\Listing\Other\ResolveTitle::NICK,
            \Ess\M2ePro\Model\Cron\Task\Amazon\Listing\Other\Channel\SynchronizeData::NICK,
            \Ess\M2ePro\Model\Cron\Task\Amazon\Listing\Other\Channel\SynchronizeData\Blocked::NICK,
            \Ess\M2ePro\Model\Cron\Task\Amazon\Listing\Product\Channel\SynchronizeData::NICK,
            \Ess\M2ePro\Model\Cron\Task\Amazon\Listing\Product\Channel\SynchronizeData\Blocked::NICK,
            \Ess\M2ePro\Model\Cron\Task\Amazon\Listing\Product\Channel\SynchronizeData\Defected::NICK,
            \Ess\M2ePro\Model\Cron\Task\Amazon\Listing\Product\RunVariationParentProcessors::NICK,
            \Ess\M2ePro\Model\Cron\Task\Amazon\Listing\Product\ProcessInstructions::NICK,
            \Ess\M2ePro\Model\Cron\Task\Amazon\Listing\Product\ProcessActions::NICK,
            \Ess\M2ePro\Model\Cron\Task\Amazon\Listing\Product\ProcessActionsResults::NICK,
            \Ess\M2ePro\Model\Cron\Task\Amazon\Order\Receive::NICK,
            \Ess\M2ePro\Model\Cron\Task\Amazon\Order\Receive\Details::NICK,
            \Ess\M2ePro\Model\Cron\Task\Amazon\Order\CreateFailed::NICK,
            \Ess\M2ePro\Model\Cron\Task\Amazon\Order\Update::NICK,
            \Ess\M2ePro\Model\Cron\Task\Amazon\Order\Update\SellerOrderId::NICK,
            \Ess\M2ePro\Model\Cron\Task\Amazon\Order\Refund::NICK,
            \Ess\M2ePro\Model\Cron\Task\Amazon\Order\Cancel::NICK,
            \Ess\M2ePro\Model\Cron\Task\Amazon\Order\ReserveCancel::NICK,
            \Ess\M2ePro\Model\Cron\Task\Amazon\Order\Action\ProcessUpdate::NICK,
            \Ess\M2ePro\Model\Cron\Task\Amazon\Order\Action\ProcessRefund::NICK,
            \Ess\M2ePro\Model\Cron\Task\Amazon\Order\Action\ProcessCancel::NICK,
            \Ess\M2ePro\Model\Cron\Task\Amazon\Order\Action\ProcessResults::NICK,
            \Ess\M2ePro\Model\Cron\Task\Amazon\Repricing\InspectProducts::NICK,
            \Ess\M2ePro\Model\Cron\Task\Amazon\Repricing\UpdateSettings::NICK,
            \Ess\M2ePro\Model\Cron\Task\Amazon\Repricing\Synchronize::NICK,
            \Ess\M2ePro\Model\Cron\Task\Walmart\Listing\Product\Channel\SynchronizeData::NICK,
            \Ess\M2ePro\Model\Cron\Task\Walmart\Listing\Product\Channel\SynchronizeData\Blocked::NICK,
            \Ess\M2ePro\Model\Cron\Task\Walmart\Listing\Other\Channel\SynchronizeData::NICK,
            \Ess\M2ePro\Model\Cron\Task\Walmart\Listing\Product\ProcessInstructions::NICK,
            \Ess\M2ePro\Model\Cron\Task\Walmart\Listing\Product\ProcessActions::NICK,
            \Ess\M2ePro\Model\Cron\Task\Walmart\Listing\Product\ProcessActionsResults::NICK,
            \Ess\M2ePro\Model\Cron\Task\Walmart\Listing\Product\ProcessListActions::NICK,
            \Ess\M2ePro\Model\Cron\Task\Walmart\Order\Receive::NICK,
            \Ess\M2ePro\Model\Cron\Task\Walmart\Order\Acknowledge::NICK,
            \Ess\M2ePro\Model\Cron\Task\Walmart\Order\Shipping::NICK,
            \Ess\M2ePro\Model\Cron\Task\Walmart\Order\Cancel::NICK,
            \Ess\M2ePro\Model\Cron\Task\Walmart\Order\Refund::NICK,
        ];
    }

    // ---------------------------------------

    /**
     * @param \Ess\M2ePro\Model\Cron\OperationHistory $operationHistory
     * @return $this
     */
    public function setParentOperationHistory(\Ess\M2ePro\Model\Cron\OperationHistory $operationHistory)
    {
        $this->parentOperationHistory = $operationHistory;
        return $this;
    }

    /**
     * @return \Ess\M2ePro\Model\Cron\OperationHistory
     */
    public function getParentOperationHistory()
    {
        return $this->parentOperationHistory;
    }

    //########################################

    abstract protected function getNick();

    //########################################

    public function process()
    {
        $this->beforeStart();

        try {

            $result = $this->processTasks();

        } catch (\Exception $exception) {

            $result = false;

            $this->getOperationHistory()->addContentData(
                'exceptions',
                [
                    'message' => $exception->getMessage(),
                    'file'    => $exception->getFile(),
                    'line'    => $exception->getLine(),
                    'trace'   => $exception->getTraceAsString(),
                ]
            );

            $this->getHelper('Module_Exception')->process($exception);
        }

        $this->afterEnd();

        return $result;
    }

    // ---------------------------------------

    /**
     * @param $taskNick
     * @return \Ess\M2ePro\Model\Cron\Task\AbstractModel
     */
    protected function getTaskObject($taskNick)
    {
        $taskNick = preg_replace_callback(
            '/_([a-z])/i',
            function ($matches) {
                return ucfirst($matches[1]);
            },
            $taskNick
        );

        $taskNick = preg_replace_callback(
            '/\/([a-z])/i',
            function ($matches) {
                return '_' . ucfirst($matches[1]);
            },
            $taskNick
        );

        $taskNick = ucfirst($taskNick);

        /** @var $task \Ess\M2ePro\Model\Cron\Task\AbstractModel **/
        $task = $this->modelFactory->getObject('Cron\Task\\'.trim($taskNick));

        $task->setInitiator($this->getInitiator());
        $task->setParentOperationHistory($this->getOperationHistory());

        return $task;
    }

    abstract protected function processTasks();

    //########################################

    protected function beforeStart()
    {
        $parentId = $this->getParentOperationHistory()
            ? $this->getParentOperationHistory()->getObject()->getId() : null;
        $this->getOperationHistory()->start('cron_strategy_'.$this->getNick(), $parentId, $this->getInitiator());
        $this->getOperationHistory()->makeShutdownFunction();
    }

    protected function afterEnd()
    {
        $this->getOperationHistory()->stop();
    }

    //########################################

    protected function keepAliveStart(\Ess\M2ePro\Model\Lock\Item\Manager $lockItemManager)
    {
        $this->observerKeepAlive->enable();
        $this->observerKeepAlive->setLockItemManager($lockItemManager);
    }

    protected function keepAliveStop()
    {
        $this->observerKeepAlive->disable();
    }

    //########################################

    protected function startListenProgressEvents(\Ess\M2ePro\Model\Lock\Item\Manager $lockItemManager)
    {
        $this->observerProgress->enable();
        $this->observerProgress->setLockItemManager($lockItemManager);
    }

    protected function stopListenProgressEvents()
    {
        $this->observerProgress->disable();
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Cron\OperationHistory
     */
    protected function getOperationHistory()
    {
        if ($this->operationHistory !== null) {
            return $this->operationHistory;
        }

        return $this->operationHistory = $this->activeRecordFactory->getObject('Cron_OperationHistory');
    }

    protected function makeLockItemShutdownFunction(\Ess\M2ePro\Model\Lock\Item\Manager $lockItemManager)
    {
        /** @var \Ess\M2ePro\Model\Lock\Item $lockItem */
        $lockItem = $this->activeRecordFactory->getObjectLoaded('Lock\Item', $lockItemManager->getNick(), 'nick');
        if (!$lockItem->getId()) {
            return;
        }

        $id = $lockItem->getId();

        register_shutdown_function(
            function () use ($id) {
                $error = error_get_last();
                if ($error === null || !in_array((int)$error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR])) {
                    return;
                }

                /** @var \Ess\M2ePro\Model\Lock\Item $lockItem */
                $lockItem = $this->activeRecordFactory->getObjectLoaded('Lock_Item', $id);
                if ($lockItem->getId()) {
                    $lockItem->delete();
                }
            }
        );
    }

    //########################################
}

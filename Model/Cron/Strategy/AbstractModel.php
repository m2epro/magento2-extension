<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Strategy;

abstract class AbstractModel extends \Ess\M2ePro\Model\AbstractModel
{
    const INITIALIZATION_TRANSACTIONAL_LOCK_NICK = 'cron_strategy_initialization';

    protected $activeRecordFactory;

    private $initiator = null;

    private $allowedTasks = NULL;

    /**
     * @var \Ess\M2ePro\Model\OperationHistory
     */
    private $operationHistory = NULL;
    /**
     * @var \Ess\M2ePro\Model\OperationHistory
     */
    private $parentOperationHistory = NULL;

    //########################################

    public function __construct(
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory
    )
    {
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
        if (!is_null($this->allowedTasks)) {
            return $this->allowedTasks;
        }

        return $this->allowedTasks = array(
            \Ess\M2ePro\Model\Cron\Task\IssuesResolver::NICK,
            \Ess\M2ePro\Model\Cron\Task\Amazon\RepricingInspectProducts::NICK,
            \Ess\M2ePro\Model\Cron\Task\Amazon\RepricingUpdateSettings::NICK,
            \Ess\M2ePro\Model\Cron\Task\Amazon\RepricingSynchronizationGeneral::NICK,
            \Ess\M2ePro\Model\Cron\Task\Amazon\RepricingSynchronizationActualPrice::NICK,
            \Ess\M2ePro\Model\Cron\Task\RequestPendingSingle::NICK,
            \Ess\M2ePro\Model\Cron\Task\RequestPendingPartial::NICK,
            \Ess\M2ePro\Model\Cron\Task\ConnectorRequesterPendingSingle::NICK,
            \Ess\M2ePro\Model\Cron\Task\ConnectorRequesterPendingPartial::NICK,
            \Ess\M2ePro\Model\Cron\Task\Amazon\Actions::NICK,
            \Ess\M2ePro\Model\Cron\Task\Ebay\Actions::NICK,
            \Ess\M2ePro\Model\Cron\Task\Servicing::NICK,
            \Ess\M2ePro\Model\Cron\Task\HealthStatus::NICK,
            \Ess\M2ePro\Model\Cron\Task\Ebay\UpdateAccountsPreferences::NICK,
            \Ess\M2ePro\Model\Cron\Task\Synchronization::NICK,
            \Ess\M2ePro\Model\Cron\Task\ArchiveOrdersEntities::NICK
        );
    }

    // ---------------------------------------

    /**
     * @param \Ess\M2ePro\Model\OperationHistory $operationHistory
     * @return $this
     */
    public function setParentOperationHistory(\Ess\M2ePro\Model\OperationHistory $operationHistory)
    {
        $this->parentOperationHistory = $operationHistory;
        return $this;
    }

    /**
     * @return \Ess\M2ePro\Model\OperationHistory
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

            $this->getOperationHistory()->addContentData('exception', array(
                'message' => $exception->getMessage(),
                'file'    => $exception->getFile(),
                'line'    => $exception->getLine(),
                'trace'   => $exception->getTraceAsString(),
            ));

            $this->getHelper('Module\Exception')->process($exception);
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
        $taskNick = ucwords($taskNick, "/_");
        $taskNick = str_replace('_', '', $taskNick);
        $taskNick = str_replace('/', '\\', $taskNick);

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
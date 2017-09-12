<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Synchronization;

use Ess\M2ePro\Model\Exception;
use Ess\M2ePro\Model\Log\AbstractModel as LogModel;

abstract class AbstractTask extends \Ess\M2ePro\Model\AbstractModel
{
    //########################################

    protected $activeRecordFactory;

    private $synchConfig = NULL;

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
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    )
    {
        $this->activeRecordFactory = $activeRecordFactory;
        parent::__construct($helperFactory, $modelFactory);
    }

    //########################################

    public function process()
    {
        $this->initialize();

        if (!$this->isPossibleToRun()) {
            return true;
        }

        $this->beforeStart();

        $result = true;

        try {

            $tempResult = $this->performActions();

            if (!is_null($tempResult) && !$tempResult) {
                $result = false;
            }

            $this->getActualLockItem()->activate();

        } catch (\Exception $exception) {

            $result = false;

            $this->processTaskException($exception);
        }

        $this->afterEnd();

        return $result;
    }

    protected function processTask($taskPath)
    {
        $result = $this->makeTask($taskPath)->process();
        return is_null($result) || $result;
    }

    protected function makeTask($taskPath)
    {
        /** @var $task \Ess\M2ePro\Model\Synchronization\AbstractTask **/
        $task = $this->modelFactory->getObject($this->buildTaskPath($taskPath));

        $task->setParentLockItem($this->getActualLockItem());
        $task->setParentOperationHistory($this->getActualOperationHistory());

        $task->setAllowedTasksTypes($this->getAllowedTasksTypes());

        $task->setLog($this->getLog());
        $task->setInitiator($this->getInitiator());
        $task->setParams($this->getParams());

        return $task;
    }

    protected function buildTaskPath($taskPath)
    {
        return 'Synchronization\\'.$taskPath;
    }

    // ---------------------------------------

    abstract protected function getType();

    abstract protected function getNick();

    // ---------------------------------------

    abstract protected function getPercentsStart();

    abstract protected function getPercentsEnd();

    // ---------------------------------------

    abstract protected function performActions();

    //########################################

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
     * @param \Ess\M2ePro\Model\Synchronization\Lock\Item\Manager $object
     */
    public function setParentLockItem(\Ess\M2ePro\Model\Synchronization\Lock\Item\Manager $object)
    {
        $this->parentLockItem = $object;
    }

    /**
     * @return \Ess\M2ePro\Model\Synchronization\Lock\Item\Manager
     */
    public function getParentLockItem()
    {
        return $this->parentLockItem;
    }

    // ---------------------------------------

    /**
     * @param \Ess\M2ePro\Model\Synchronization\OperationHistory $object
     */
    public function setParentOperationHistory(\Ess\M2ePro\Model\Synchronization\OperationHistory $object)
    {
        $this->parentOperationHistory = $object;
    }

    /**
     * @return \Ess\M2ePro\Model\Synchronization\OperationHistory
     */
    public function getParentOperationHistory()
    {
        return $this->parentOperationHistory;
    }

    // ---------------------------------------

    /**
     * @param \Ess\M2ePro\Model\Synchronization\Log $object
     */
    public function setLog(\Ess\M2ePro\Model\Synchronization\Log $object)
    {
        $this->log = $object;
    }

    /**
     * @return \Ess\M2ePro\Model\Synchronization\Log
     */
    public function getLog()
    {
        return $this->log;
    }

    //########################################

    protected function initialize() {}

    protected function isPossibleToRun()
    {
        if ($this->isContainerTask() &&
            !in_array($this->getType(),$this->getAllowedTasksTypes())) {
            return false;
        }

        $tempSettingsPath = '/';
        foreach (array_values(array_filter(explode('/',$this->getFullSettingsPath()))) as $node) {

            $tempSettingsPath .= $node.'/';
            $tempMode = $this->getConfigValue($tempSettingsPath,'mode');

            if (!is_null($tempMode) && !$tempMode) {
                return false;
            }
        }

        if (!$this->getParentLockItem() && $this->getLockItem()->isExist()) {
            return false;
        }

        if ($this->intervalIsEnabled() && $this->intervalIsLocked()) {
            return false;
        }

        return true;
    }

    // ---------------------------------------

    protected function beforeStart()
    {
        if (!$this->getParentLockItem()) {
            if ($this->getLockItem()->isExist()) {
                throw new Exception('Lock item "'.$this->getLockItem()->getNick().'" already exists.');
            }

            $this->getLockItem()->create();
            $this->getLockItem()->makeShutdownFunction();
        }

        if (!$this->getParentOperationHistory() || $this->isLauncherTask() || $this->isContainerTask()) {

            $operationHistoryNickSuffix = str_replace('/','_',trim($this->getFullSettingsPath(),'/'));

            $operationHistoryParentId = $this->getParentOperationHistory() ?
                    $this->getParentOperationHistory()->getObject()->getId() : NULL;

            $this->getOperationHistory()->start('synchronization_'.$operationHistoryNickSuffix,
                                                $operationHistoryParentId,
                                                $this->getInitiator());

            $this->getOperationHistory()->makeShutdownFunction();
        }

        $this->configureLogBeforeStart();
        $this->configureProfilerBeforeStart();
        $this->configureLockItemBeforeStart();
    }

    protected function afterEnd()
    {
        $this->configureLockItemAfterEnd();
        $this->configureProfilerAfterEnd();
        $this->configureLogAfterEnd();

        if ($this->intervalIsEnabled()) {
            $this->intervalSetLastTime($this->getHelper('Data')->getCurrentGmtDate(true));
        }

        if (!$this->getParentOperationHistory() || $this->isLauncherTask() || $this->isContainerTask()) {
            $this->getOperationHistory()->stop();
        }

        if (!$this->getParentLockItem()) {
            $this->getLockItem()->remove();
        }
    }

    //########################################

    protected function getOperationHistory()
    {
        if (is_null($this->operationHistory)) {
            $this->operationHistory = $this->activeRecordFactory
                                           ->getObject('Synchronization\OperationHistory');
        }
        return $this->operationHistory;
    }

    protected function getLockItem()
    {
        if (is_null($this->lockItem)) {
            $this->lockItem = $this->modelFactory->getObject('Synchronization\Lock\Item\Manager');
            $operationHistoryNickSuffix = str_replace('/','_',trim($this->getFullSettingsPath(),'/'));
            $this->lockItem->setNick('synchronization_'.$operationHistoryNickSuffix);
        }
        return $this->lockItem;
    }

    // ---------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Synchronization\OperationHistory
     * @throws \Ess\M2ePro\Model\Exception
     */
    protected function getActualOperationHistory()
    {
        if ($this->operationHistory) {
            return $this->operationHistory;
        }

        if (!$this->getParentOperationHistory()) {
            throw new \Ess\M2ePro\Model\Exception('Parent Operation History must be specified');
        }

        return $this->getParentOperationHistory();
    }

    /**
     * @return \Ess\M2ePro\Model\Synchronization\Lock\Item\Manager
     * @throws \Ess\M2ePro\Model\Exception
     */
    protected function getActualLockItem()
    {
        if ($this->lockItem) {
            return $this->lockItem;
        }

        if (!$this->getParentLockItem()) {
            throw new \Ess\M2ePro\Model\Exception('Parent Lock Item must be specified');
        }

        return $this->getParentLockItem();
    }

    //########################################

    /**
     * @return bool
     */
    protected function isLauncherTask()
    {
        return !(bool)$this->getType() && !(bool)$this->getNick();
    }

    /**
     * @return bool
     */
    protected function isContainerTask()
    {
        return (bool)$this->getType() && !(bool)$this->getNick();
    }

    /**
     * @return bool
     */
    protected function isStandardTask()
    {
        return !$this->isLauncherTask() && !$this->isContainerTask();
    }

    //########################################

    /**
     * @return string
     */
    protected function getTitle()
    {
        if ($this->isContainerTask()) {
            $title = ucfirst($this->getType());
        } else {
            $title = ucwords(str_replace('/',' ',trim($this->getNick(),'/')));
        }

        return $title;
    }

    /**
     * @return int
     */
    protected function getLogTask()
    {
        return \Ess\M2ePro\Model\Synchronization\Log::TASK_UNKNOWN;
    }

    // ---------------------------------------

    /**
     * @return string
     */
    protected function getFullSettingsPath()
    {
        $path = '/' . ($this->getType() ? strtolower($this->getType()).'/' : '');
        $path .= $this->getNick() ? trim(strtolower($this->getNick())).'/' : '';
        return $path;
    }

    /**
     * @return int
     */
    protected function getPercentsInterval()
    {
        return $this->getPercentsEnd() - $this->getPercentsStart();
    }

    //########################################

    protected function configureLogBeforeStart()
    {
        if ($this->isContainerTask()) {
            $this->getLog()->setSynchronizationTask($this->getLogTask());
        }
    }

    protected function configureLogAfterEnd()
    {
        if ($this->isContainerTask()) {
            $this->getLog()->setSynchronizationTask(\Ess\M2ePro\Model\Synchronization\Log::TASK_UNKNOWN);
        }
    }

    // ---------------------------------------

    protected function configureProfilerBeforeStart()
    {
        if (!$this->isStandardTask()) {
            $this->getActualOperationHistory()->increaseLeftPadding();
            return;
        }

        $this->getActualOperationHistory()->appendEol();
        $this->getActualOperationHistory()->appendText($this->getTitle());
        $this->getActualOperationHistory()->appendLine();

        $this->getActualOperationHistory()->saveBufferString();

        $this->getActualOperationHistory()->increaseLeftPadding();
    }

    protected function configureProfilerAfterEnd()
    {
        $this->getActualOperationHistory()->decreaseLeftPadding();

        if ($this->isStandardTask()) {
            $this->getActualOperationHistory()->appendLine();
        }

        $this->getActualOperationHistory()->saveBufferString();
    }

    // ---------------------------------------

    protected function configureLockItemBeforeStart()
    {
        $suffix = $this->getHelper('Module\Translation')->__('Synchronization');

        if ($this->isLauncherTask() || $this->isContainerTask()) {

            $title = $suffix;

            if ($this->isContainerTask()) {
                $title = $this->getTitle().' '.$title;
            }

            $this->getActualLockItem()->setTitle($this->getHelper('Module\Translation')->__($title));
        }

        $this->getActualLockItem()->setPercents($this->getPercentsStart());

        // M2ePro\TRANSLATIONS
        // Task "%task_title%" is started. Please wait...
        $status = 'Task "%task_title%" is started. Please wait...';
        $title = ($this->isLauncherTask() || $this->isContainerTask()) ?
                    $this->getTitle().' '.$suffix : $this->getTitle();

        $this->getActualLockItem()->setStatus($this->getHelper('Module\Translation')->__($status,$title));
    }

    protected function configureLockItemAfterEnd()
    {
        $suffix = $this->getHelper('Module\Translation')->__('Synchronization');

        if ($this->isLauncherTask() || $this->isContainerTask()) {

            $title = $suffix;

            if ($this->isContainerTask()) {
                $title = $this->getTitle().' '.$title;
            }

            $this->getActualLockItem()->setTitle($this->getHelper('Module\Translation')->__($title));
        }

        $this->getActualLockItem()->setPercents($this->getPercentsEnd());

        // M2ePro\TRANSLATIONS
        // Task "%task_title%" is finished. Please wait...
        $status = 'Task "%task_title%" is finished. Please wait...';
        $title = ($this->isLauncherTask() || $this->isContainerTask()) ?
                    $this->getTitle().' '.$suffix : $this->getTitle();

        $this->getActualLockItem()->setStatus($this->getHelper('Module\Translation')->__($status,$title));
    }

    //########################################

    protected function intervalIsEnabled()
    {
        return false;
    }

    protected function intervalIsLocked()
    {
        $lastTime = $this->intervalGetLastTime();
        if (empty($lastTime)) {
            return false;
        }

        $interval = (int)$this->getConfigValue($this->getFullSettingsPath(),'interval');
        return strtotime($lastTime) + $interval > $this->getHelper('Data')->getCurrentGmtDate(true);
    }

    // ---------------------------------------

    protected function intervalSetLastTime($time)
    {
        if ($time instanceof \DateTime) {
            $time = (int)$time->format('U');
        }

        if (is_int($time)) {
            $oldTimezone = date_default_timezone_get();
            date_default_timezone_set('UTC');
            $time = strftime('%Y-%m-%d %H:%M:%S', $time);
            date_default_timezone_set($oldTimezone);
        }

        $this->setConfigValue($this->getFullSettingsPath(),'last_time',$time);
    }

    protected function intervalGetLastTime()
    {
        return $this->getConfigValue($this->getFullSettingsPath(),'last_time');
    }

    //########################################

    private function getConfig()
    {
        if (is_null($this->synchConfig)) {
            $this->synchConfig = $this->modelFactory->getObject('Config\Manager\Synchronization');
        }

        return $this->synchConfig;
    }

    // ---------------------------------------

    protected function getConfigValue($group, $key)
    {
        return $this->getConfig()->getGroupValue($group, $key);
    }

    protected function setConfigValue($group, $key, $value)
    {
        return $this->getConfig()->setGroupValue($group, $key, $value);
    }

    //########################################

    protected function processTaskException(\Exception $exception)
    {
        $this->getActualOperationHistory()->addContentData('exceptions', array(
            'message' => $exception->getMessage(),
            'file'    => $exception->getFile(),
            'line'    => $exception->getLine(),
            'trace'   => $exception->getTraceAsString(),
        ));

        $this->getLog()->addMessage(
            $this->getHelper('Module\Translation')->__($exception->getMessage()),
            LogModel::TYPE_ERROR,
            LogModel::PRIORITY_HIGH
        );

        $this->getHelper('Module\Exception')->process($exception);
    }

    protected function processTaskAccountException($message, $file, $line, $trace = null)
    {
        $this->getActualOperationHistory()->addContentData('exceptions', array(
            'message' => $message,
            'file'    => $file,
            'line'    => $line,
            'trace'   => $trace,
        ));

        $this->getLog()->addMessage(
            $message,
            LogModel::TYPE_ERROR,
            LogModel::PRIORITY_HIGH
        );
    }

    //########################################
}
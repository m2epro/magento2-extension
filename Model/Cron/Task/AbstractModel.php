<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task;

use Ess\M2ePro\Model\Exception;

abstract class AbstractModel extends \Ess\M2ePro\Model\AbstractModel
{
    private $initiator = \Ess\M2ePro\Helper\Data::INITIATOR_UNKNOWN;

    protected $parentFactory;

    protected $activeRecordFactory;

    protected $resource;

    /**
     * @var \Ess\M2ePro\Model\Lock\Item\Manager
     */
    private $lockItem       = NULL;
    /**
     * @var \Ess\M2ePro\Model\Lock\Item\Manager
     */
    private $parentLockItem = NULL;

    /**
     * @var \Ess\M2ePro\Model\OperationHistory
     */
    private $operationHistory       = NULL;
    /**
     * @var \Ess\M2ePro\Model\OperationHistory
     */
    private $parentOperationHistory = NULL;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\App\ResourceConnection $resource
    )
    {
        parent::__construct($helperFactory, $modelFactory);
        $this->parentFactory = $parentFactory;
        $this->activeRecordFactory = $activeRecordFactory;
        $this->resource = $resource;
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

            $tempResult = $this->performActions();

            if (!is_null($tempResult) && !$tempResult) {
                $result = false;
            }

            $this->getLockItem()->activate();

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

    abstract protected function getNick();

    abstract protected function getMaxMemoryLimit();

    // ---------------------------------------

    abstract protected function performActions();

    //########################################

    public function setInitiator($value)
    {
        $this->initiator = (int)$value;
    }

    public function getInitiator()
    {
        return $this->initiator;
    }

    // ---------------------------------------

    /**
     * @param \Ess\M2ePro\Model\Lock\Item\Manager $object
     * @return $this
     */
    public function setParentLockItem(\Ess\M2ePro\Model\Lock\Item\Manager $object)
    {
        $this->parentLockItem = $object;
        return $this;
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
     * @return $this
     */
    public function setParentOperationHistory(\Ess\M2ePro\Model\OperationHistory $object)
    {
        $this->parentOperationHistory = $object;
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

    /**
     * @return bool
     */
    public function isPossibleToRun()
    {
        $currentTimeStamp = $this->getHelper('Data')->getCurrentGmtDate(true);

        $startFrom = $this->getConfigValue('start_from');
        $startFrom = !empty($startFrom) ? strtotime($startFrom) : $currentTimeStamp;

        return $this->isModeEnabled() &&
               (($startFrom <= $currentTimeStamp && $this->isIntervalExceeded()) ||
                 $this->getInitiator() == \Ess\M2ePro\Helper\Data::INITIATOR_DEVELOPER) &&
               !$this->getLockItem()->isExist();
    }

    //########################################

    protected function initialize()
    {
        $this->getHelper('Client')->setMemoryLimit($this->getMaxMemoryLimit());
        $this->getHelper('Module\Exception')->setFatalErrorHandler();
    }

    protected function updateLastAccess()
    {
        $this->setConfigValue('last_access',$this->getHelper('Data')->getCurrentGmtDate());
    }

    protected function updateLastRun()
    {
        $this->setConfigValue('last_run',$this->getHelper('Data')->getCurrentGmtDate());
    }

    // ---------------------------------------

    protected function beforeStart()
    {
        if ($this->getLockItem()->isExist()) {
            throw new Exception('Lock item "'.$this->getLockItem()->getNick().'" already exists.');
        }

        $parentId = $this->getParentLockItem() ? $this->getParentLockItem()->getRealId() : null;
        $this->getLockItem()->create($parentId);
        $this->getLockItem()->makeShutdownFunction();

        $parentId = $this->getParentOperationHistory()
            ? $this->getParentOperationHistory()->getObject()->getId() : null;
        $nick = str_replace("/", "_", $this->getNick());
        $this->getOperationHistory()->start('cron_task_'.$nick, $parentId, $this->getInitiator());
        $this->getOperationHistory()->makeShutdownFunction();
    }

    protected function afterEnd()
    {
        $this->getOperationHistory()->stop();
        $this->getLockItem()->remove();
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Lock\Item\Manager
     */
    protected function getLockItem()
    {
        if (!is_null($this->lockItem)) {
            return $this->lockItem;
        }

        $this->lockItem = $this->modelFactory->getObject('Lock\Item\Manager');
        $this->lockItem->setNick('cron_task_'.str_replace("/", "_", $this->getNick()));

        return $this->lockItem;
    }

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

    // ---------------------------------------

    /**
     * @return bool
     */
    protected function isModeEnabled()
    {
        return (bool)$this->getConfigValue('mode');
    }

    /**
     * @return bool
     */
    protected function isIntervalExceeded()
    {
        $lastRun = $this->getConfigValue('last_run');

        if (is_null($lastRun)) {
            return true;
        }

        $interval = (int)$this->getConfigValue('interval');
        $currentTimeStamp = $this->getHelper('Data')->getCurrentGmtDate(true);

        return $currentTimeStamp > strtotime($lastRun) + $interval;
    }

    //########################################

    private function getConfig()
    {
        return $this->getHelper('Module')->getConfig();
    }

    private function getConfigGroup()
    {
        return '/cron/task/'.$this->getNick().'/';
    }

    // ---------------------------------------

    private function getConfigValue($key)
    {
        return $this->getConfig()->getGroupValue($this->getConfigGroup(), $key);
    }

    private function setConfigValue($key, $value)
    {
        return $this->getConfig()->setGroupValue($this->getConfigGroup(), $key, $value);
    }

    //########################################
}
<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model;

use \Ess\M2ePro\Helper\Data as Helper;

/**
 * Class \Ess\M2ePro\Model\OperationHistory
 */
class OperationHistory extends \Ess\M2ePro\Model\ActiveRecord\AbstractModel
{
    const MAX_LIFETIME_INTERVAL = 432000; // 5 days

    /**
     * @var OperationHistory
     */
    private $object = null;

    /** @var \Ess\M2ePro\Helper\Data */
    private $helperData;

    //########################################
    public function __construct(
        \Ess\M2ePro\Helper\Data $helperData,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->helperData = $helperData;
        parent::__construct(
            $modelFactory,
            $activeRecordFactory,
            $helperFactory,
            $context,
            $registry,
            $resource,
            $resourceCollection,
            $data
        );
    }

    public function _construct()
    {
        parent::_construct();
        $this->_init(\Ess\M2ePro\Model\ResourceModel\OperationHistory::class);
    }

    //########################################

    public function setObject($value)
    {
        if (is_object($value)) {
            $this->object = $value;
        } else {
            $this->object = $this->activeRecordFactory->getObject('OperationHistory')->load($value);
            !$this->object->getId() && $this->object = null;
        }

        return $this;
    }

    /**
     * @return OperationHistory
     */
    public function getObject()
    {
        return $this->object;
    }

    //########################################

    /**
     * @param string $nick
     *
     * @return \Ess\M2ePro\Model\OperationHistory
     */
    public function getParentObject($nick = null)
    {
        if ($this->getObject()->getData('parent_id') === null) {
            return null;
        }

        $parentId = (int)$this->getObject()->getData('parent_id');
        $parentObject = $this->activeRecordFactory->getObjectLoaded('OperationHistory', $parentId);

        if ($nick === null) {
            return $parentObject;
        }

        while ($parentObject->getData('nick') != $nick) {
            $parentId = $parentObject->getData('parent_id');
            if ($parentId === null) {
                return null;
            }

            $parentObject = $this->activeRecordFactory->getObjectLoaded('OperationHistory', $parentId);
        }

        return $parentObject;
    }

    //########################################

    public function start($nick, $parentId = null, $initiator = Helper::INITIATOR_UNKNOWN, array $data = [])
    {
        $data = [
            'nick' => $nick,
            'parent_id'  => $parentId,
            'data'       => $this->helperData->jsonEncode($data),
            'initiator'  => $initiator,
            'start_date' => $this->helperData->getCurrentGmtDate()
        ];

        $this->object = $this->activeRecordFactory
                             ->getObject('OperationHistory')
                             ->setData($data)
                             ->save();

        return true;
    }

    public function stop()
    {
        if ($this->object === null || $this->object->getData('end_date')) {
            return false;
        }

        $this->object->setData(
            'end_date',
            $this->helperData->getCurrentGmtDate()
        )->save();

        return true;
    }

    //########################################

    public function setContentData($key, $value)
    {
        if ($this->object === null) {
            return false;
        }

        $data = [];
        if ($this->object->getData('data') != '') {
            $data = $this->helperData->jsonDecode($this->object->getData('data'));
        }

        $data[$key] = $value;
        $this->object->setData(
            'data',
            $this->helperData->jsonEncode($data)
        )->save();

        return true;
    }

    public function addContentData($key, $value)
    {
        $existedData = $this->getContentData($key);

        if ($existedData === null) {
            is_array($value) ? $existedData = [$value] : $existedData = $value;
            return $this->setContentData($key, $existedData);
        }

        is_array($existedData) ? $existedData[] = $value : $existedData .= $value;
        return $this->setContentData($key, $existedData);
    }

    public function getContentData($key)
    {
        if ($this->object === null) {
            return null;
        }

        if ($this->object->getData('data') == '') {
            return null;
        }

        $data = $this->helperData->jsonDecode($this->object->getData('data'));

        if (isset($data[$key])) {
            return $data[$key];
        }

        return null;
    }

    //########################################

    public function cleanOldData()
    {
        $minDate = new \DateTime('now', new \DateTimeZone('UTC'));
        $minDate->modify('-'.self::MAX_LIFETIME_INTERVAL.' seconds');

        $this->getResource()->getConnection()->delete(
            $this->getResource()->getMainTable(),
            ['`start_date` <= ?' => $minDate->format('Y-m-d H:i:s')]
        );
    }

    public function makeShutdownFunction()
    {
        if ($this->object === null) {
            return false;
        }

        $objectId = $this->object->getId();
        register_shutdown_function(function () use ($objectId) {
            $error = error_get_last();
            if ($error === null || !in_array((int)$error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR])) {
                return;
            }

            /** @var OperationHistory $object */
            $object = $this->activeRecordFactory->getObject('OperationHistory');
            $object->setObject($objectId);

            if (!$object->stop()) {
                return;
            }

            $collection = $object->getCollection()->addFieldToFilter('parent_id', $objectId);
            if ($collection->getSize()) {
                return;
            }

             $stackTrace = debug_backtrace(false);
             $object->setContentData('fatal_error', [
                'message' => $error['message'],
                'file' => $error['file'],
                'line' => $error['line'],
                'trace' => $this->getHelper('Module\Exception')->getFatalStackTraceInfo($stackTrace)
             ]);
        });

        return true;
    }

    //########################################

    public function getDataInfo($nestingLevel = 0)
    {
        if ($this->object === null) {
            return null;
        }

        $offset = str_repeat(' ', $nestingLevel * 7);
        $separationLine = str_repeat('#', 80 - strlen($offset));

        $nick = strtoupper($this->getObject()->getData('nick'));

        $contentData = (array)$this->helperData->jsonDecode($this->getObject()->getData('data'));
        $contentData = preg_replace('/^/m', "{$offset}", print_r($contentData, true));

        return <<<INFO
{$offset}{$nick}
{$offset}Start Date: {$this->getObject()->getData('start_date')}
{$offset}End Date: {$this->getObject()->getData('end_date')}
{$offset}Total Time: {$this->getTotalTime()}

{$offset}{$separationLine}
{$contentData}
{$offset}{$separationLine}

INFO;
    }

    public function getFullDataInfo($nestingLevel = 0)
    {
        if ($this->object === null) {
            return null;
        }

        $dataInfo = $this->getDataInfo($nestingLevel);

        $childObjects = $this->getCollection()
                             ->addFieldToFilter('parent_id', $this->getObject()->getId())
                             ->setOrder('start_date', 'ASC');

        $childObjects->getSize() > 0 && $nestingLevel++;

        foreach ($childObjects as $item) {

            /** @var OperationHistory $object */
            $object = $this->activeRecordFactory->getObject('OperationHistory');
            $object->setObject($item);

            $dataInfo .= $object->getFullDataInfo($nestingLevel);
        }

        return $dataInfo;
    }

    // ---------------------------------------

    public function getExecutionInfo($nestingLevel = 0)
    {
        if ($this->object === null) {
            return null;
        }

        $offset = str_repeat(' ', $nestingLevel * 5);

        return <<<INFO
{$offset}<b>{$this->getObject()->getData('nick')} ## {$this->getObject()->getData('id')}</b>
{$offset}start date: {$this->getObject()->getData('start_date')}
{$offset}end date:   {$this->getObject()->getData('end_date')}
{$offset}total time: {$this->getTotalTime()}
<br>
INFO;
    }

    public function getExecutionTreeUpInfo()
    {
        if ($this->object === null) {
            return null;
        }

        $extraParent = $this->getObject();
        $executionTree[] = $extraParent;

        while ($parentId = $extraParent->getData('parent_id')) {
            $extraParent = $this->activeRecordFactory->getObject('OperationHistory')->load($parentId);
            $executionTree[] = $extraParent;
        }

        $info = '';
        $executionTree = array_reverse($executionTree);

        foreach ($executionTree as $nestingLevel => $item) {
            $object = $this->activeRecordFactory->getObject('OperationHistory');
            $object->setObject($item);

            $info .= $object->getExecutionInfo($nestingLevel);
        }

        return $info;
    }

    public function getExecutionTreeDownInfo($nestingLevel = 0)
    {
        if ($this->object === null) {
            return null;
        }

        $info = $this->getExecutionInfo($nestingLevel);

        $childObjects = $this->getCollection()
            ->addFieldToFilter('parent_id', $this->getObject()->getId())
            ->setOrder('start_date', 'ASC');

        $childObjects->getSize() > 0 && $nestingLevel++;

        foreach ($childObjects as $item) {
            $object = $this->activeRecordFactory->getObject('OperationHistory');
            $object->setObject($item);

            $info .= $object->getExecutionTreeDownInfo($nestingLevel);
        }

        return $info;
    }

    // ---------------------------------------

    protected function getTotalTime()
    {
        $endDateTimestamp = (int)$this->helperData
            ->createGmtDateTime($this->getObject()->getData('end_date'))
            ->format('U');
        $startDateTimestamp = (int)$this->helperData
            ->createGmtDateTime($this->getObject()->getData('start_date'))
            ->format('U');
        $totalTime = $endDateTimestamp - $startDateTimestamp;

        if ($totalTime < 0) {
            return 'n/a';
        }

        $minutes = (int)($totalTime / 60);
        $minutes < 10 && $minutes = '0'.$minutes;

        $seconds = $totalTime - $minutes * 60;
        $seconds < 10 && $seconds = '0'.$seconds;

        return "{$minutes}:{$seconds}";
    }

    //########################################
}

<?php

namespace Ess\M2ePro\Model;

class OperationHistory extends \Ess\M2ePro\Model\ActiveRecord\AbstractModel
{
    const MAX_LIFETIME_INTERVAL = 864000; // 10 days

    /**
     * @var OperationHistory
     */
    private $object = NULL;

    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('Ess\M2ePro\Model\ResourceModel\OperationHistory');
    }

    //########################################

    public function setObject($value)
    {
        if (is_object($value)) {
            $this->object = $value;
        } else {
            $this->object = $this->activeRecordFactory->getObject('OperationHistory')->load($value);
            !$this->object->getId() && $this->object = NULL;
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
     * @param $nick string
     * @return \Ess\M2ePro\Model\OperationHistory
     */
    public function getParentObject($nick = NULL)
    {
        if (is_null($this->getObject()->getData('parent_id'))) {
            return NULL;
        }

        $parentId = (int)$this->getObject()->getData('parent_id');
        $parentObject = $this->activeRecordFactory->getObjectLoaded('OperationHistory', $parentId);

        if (is_null($nick)) {
            return $parentObject;
        }

        while ($parentObject->getData('nick') != $nick) {
            $parentId = $parentObject->getData('parent_id');
            if (is_null($parentId)) {
                return NULL;
            }

            $parentObject = $this->activeRecordFactory->getObjectLoaded('OperationHistory', $parentId);
        }

        return $parentObject;
    }

    //########################################

    public function start($nick, $parentId = NULL, $initiator = \Ess\M2ePro\Helper\Data::INITIATOR_UNKNOWN)
    {
        $data = array(
            'nick' => $nick,
            'parent_id' => $parentId,
            'initiator' => $initiator,
            'start_date' => $this->getHelper('Data')->getCurrentGmtDate()
        );

        $this->object = $this->activeRecordFactory
                             ->getObject('OperationHistory')
                             ->setData($data)->save();

        return true;
    }

    public function stop()
    {
        if (is_null($this->object) || $this->object->getData('end_date')) {
            return false;
        }

        $this->object->setData(
            'end_date', $this->getHelper('Data')->getCurrentGmtDate()
        )->save();

        return true;
    }

    //########################################

    public function setContentData($key, $value)
    {
        if (is_null($this->object)) {
            return false;
        }

        $data = array();
        if ($this->object->getData('data') != '') {
            $data = json_decode($this->object->getData('data'), true);
        }

        $data[$key] = $value;
        $this->object->setData('data',json_encode($data))->save();

        return true;
    }

    public function addContentData($key, $value)
    {
        $existedData = $this->getContentData($key);

        if (is_null($existedData)) {

            is_array($value) ? $existedData = array($value) : $existedData = $value;
            return $this->setContentData($key, $existedData);
        }

        is_array($existedData) ? $existedData[] = $value : $existedData .= $value;
        return $this->setContentData($key, $existedData);
    }

    public function getContentData($key)
    {
        if (is_null($this->object)) {
            return NULL;
        }

        if ($this->object->getData('data') == '') {
            return NULL;
        }

        $data = json_decode($this->object->getData('data'), true);

        if (isset($data[$key])) {
            return $data[$key];
        }

        return NULL;
    }

    //########################################

    public function cleanOldData()
    {
        $minDate = new \DateTime('now', new \DateTimeZone('UTC'));
        $minDate->modify('-'.self::MAX_LIFETIME_INTERVAL.' seconds');

        $this->getResource()->getConnection()->delete(
            $this->getResource()->getMainTable(),
            ['`create_date` <= ?' => $minDate->format('Y-m-d H:i:s')]
        );
    }

    public function makeShutdownFunction()
    {
        if (is_null($this->object)) {
            return false;
        }

        $objectId = $this->object->getId();
        register_shutdown_function(function() use($objectId) {
            /** @var OperationHistory $object */
            $object = $this->activeRecordFactory->getObject('OperationHistory');
            $object->setObject($objectId);

            if (!$object->stop()) {
            return;
            }

            $collection = $object->getCollection()
                 ->addFieldToFilter('parent_id', $objectId);

            if ($collection->getSize()) {
                return;
            }

            $error = error_get_last();

            if (is_null($error)) {
                return;
            }

            if (in_array((int)$error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR])) {
             $stackTrace = debug_backtrace(false);
             $object->setContentData('fatal_error', [
                'message' => $error['message'],
                'file' => $error['file'],
                'line' => $error['line'],
                'trace' => $this->getHelper('Module\Exception')->getFatalStackTraceInfo($stackTrace)
             ]);
            }
        });

        return true;
    }

    //########################################

    public function getDataInfo($nestingLevel = 0)
    {
        if (is_null($this->object)) {
            return NULL;
        }

        $offset = str_repeat(' ', $nestingLevel * 7);
        $separationLine = str_repeat('#', 80 - strlen($offset));

        $nick = strtoupper($this->getObject()->getData('nick'));

        $contentData = (array)json_decode($this->getObject()->getData('data'), true);
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
        if (is_null($this->object)) {
            return NULL;
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

    protected function getTotalTime()
    {
        $totalTime = strtotime($this->getObject()->getData('end_date')) -
                     strtotime($this->getObject()->getData('start_date'));

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
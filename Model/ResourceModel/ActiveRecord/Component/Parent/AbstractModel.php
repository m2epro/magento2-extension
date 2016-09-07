<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\ActiveRecord\Component\Parent;

abstract class AbstractModel extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Component\AbstractModel
{
    protected $childMode = NULL;

    //########################################

    public function setChildMode($mode)
    {
        $mode = strtolower((string)$mode);
        $mode && $this->childMode = $mode;
        return $this;
    }

    public function getChildMode()
    {
        return $this->childMode;
    }

    // ---------------------------------------

    public function getChildModel()
    {
        if (is_null($this->childMode)) {
            return NULL;
        }

        return str_replace('Ess\M2ePro\Model\ResourceModel',ucwords($this->childMode), get_class($this));
    }

    public function getChildTable()
    {
        if (is_null($this->childMode)) {
            return NULL;
        }

        return str_replace('m2epro_','m2epro_'.$this->childMode.'_',$this->getMainTable());
    }

    public function getChildPrimary()
    {
        if (is_null($this->childMode)) {
            return NULL;
        }

        $secondTable = $this->getChildTable();

        $primaryName = substr($secondTable,strpos($secondTable,'m2epro_'.$this->childMode.'_'));
        return substr($primaryName,strlen('m2epro_'.$this->childMode.'_')).'_id';
    }

    //########################################

    protected function _getLoadSelect($field, $value, $object)
    {
        $select = parent::_getLoadSelect($field, $value, $object);

        if (is_null($this->childMode)) {
            return $select;
        }

        $childTable = $this->getChildTable();
        $select->join(
            $childTable,
            "`{$childTable}`.`".$this->getChildPrimary()."` = `".$this->getMainTable().'`.`id`'
        );

        return $select;
    }

    protected function _afterLoad(\Magento\Framework\Model\AbstractModel $object)
    {
        $result = parent::_afterLoad($object);

        if (is_null($this->childMode)) {
            return $result;
        }

        $object->setChildMode($this->childMode);

        if ($object->isEmpty()) {
            return $result;
        }

        $data = $object->getData();
        $object->unsetData();

        $childObject = $this->activeRecordFactory->getObject($this->getChildModel());

        $childColumnsData = $this->getConnection()->describeTable($this->getChildTable());
        foreach($childColumnsData as $columnData) {
            $childObject->setData($columnData['COLUMN_NAME'], $data[$columnData['COLUMN_NAME']]);
            unset($data[$columnData['COLUMN_NAME']]);
        }

        $object->setData($data);
        $object->setOrigData();

        $childObject->setParentObject($object);
        $childObject->setOrigData();

        $object->setChildObject($childObject);

        return $result;
    }

    //########################################

    protected function _beforeSave(\Magento\Framework\Model\AbstractModel $object)
    {
        $result = parent::_beforeSave($object);

        if (!$object->getId()) {
            $object->setCreateMode(true);
        }

        return $result;
    }

    protected function _afterSave(\Magento\Framework\Model\AbstractModel $object)
    {
        /** @var \Ess\M2ePro\Model\ActiveRecord\Component\Parent\AbstractModel $object */

        $result = parent::_afterSave($object);

        if (is_null($this->childMode)) {
            return $result;
        }

        if (!$object->isCreateMode()) {
            return $result;
        }

        $object->setCreateMode(false);

        $data = $object->getData();
        $data[$this->getChildPrimary()] = (int)$object->getData('id');
        $dataColumns = array_keys($data);

        $object->unsetData();

        $parentColumnsInfo = $this->getConnection()->describeTable($this->getMainTable());

        foreach($parentColumnsInfo as $columnInfo) {
            if (in_array($columnInfo['COLUMN_NAME'], $dataColumns)) {
                $object->setData($columnInfo['COLUMN_NAME'], $data[$columnInfo['COLUMN_NAME']]);
            }
        }
        $object->setOrigData();

        /** @var \Ess\M2ePro\Model\ActiveRecord\Component\Child\AbstractModel $childObject */
        $childObject = $this->activeRecordFactory->getObject($this->getChildModel());

        $childColumnsInfo = $this->getConnection()->describeTable($this->getChildTable());
        foreach($childColumnsInfo as $columnInfo) {
            if (in_array($columnInfo['COLUMN_NAME'], $dataColumns)) {
                $childObject->setData($columnInfo['COLUMN_NAME'], $data[$columnInfo['COLUMN_NAME']]);
            }
        }

        $childObject->save();
        $childObject->setParentObject($object);

        $object->setChildObject($childObject);

        if ($object->getData('reload_on_create')) {
            $object->load($object->getId());
        }

        return $result;
    }

    //########################################
}
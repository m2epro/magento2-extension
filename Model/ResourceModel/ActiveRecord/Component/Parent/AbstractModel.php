<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\ActiveRecord\Component\Parent;

use  Ess\M2ePro\Model\ActiveRecord\Component\Parent\AbstractModel as ParentActiveRecordAbstract;
use  Ess\M2ePro\Model\ActiveRecord\Component\Child\AbstractModel as ChildActiveRecordAbstract;

abstract class AbstractModel extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Component\AbstractModel
{
    //########################################

    public function getChildModel($childMode)
    {
        if (is_null($childMode)) {
            return NULL;
        }

        return str_replace('Ess\M2ePro\Model\ResourceModel',ucwords($childMode), get_class($this));
    }

    public function getChildTable($childMode)
    {
        if (is_null($childMode)) {
            return NULL;
        }

        return str_replace('m2epro_','m2epro_'.$childMode.'_',$this->getMainTable());
    }

    public function getChildPrimary($childMode)
    {
        if (is_null($childMode)) {
            return NULL;
        }

        $secondTable = $this->getChildTable($childMode);

        $primaryName = substr($secondTable,strpos($secondTable,'m2epro_'.$childMode.'_'));
        return substr($primaryName,strlen('m2epro_'.$childMode.'_')).'_id';
    }

    //########################################

    protected function _getLoadSelect($field, $value, $object)
    {
        /** @var ParentActiveRecordAbstract $object */

        $select = parent::_getLoadSelect($field, $value, $object);

        if (is_null($object->getChildMode())) {
            return $select;
        }

        $childTable = $this->getChildTable($object->getChildMode());
        $select->join(
            $childTable,
            "`{$childTable}`.`".$this->getChildPrimary($object->getChildMode())."` = `".$this->getMainTable().'`.`id`'
        );

        return $select;
    }

    protected function _afterLoad(\Magento\Framework\Model\AbstractModel $object)
    {
        /** @var ParentActiveRecordAbstract $object */

        $result = parent::_afterLoad($object);

        if (is_null($object->getChildMode())) {
            return $result;
        }

        if ($object->isEmpty()) {
            return $result;
        }

        $data = $object->getData();
        $object->unsetData();

        /** @var ChildActiveRecordAbstract $childObject */
        $childObject = $this->activeRecordFactory->getObject($this->getChildModel($object->getChildMode()));

        $childColumnsData = $this->getConnection()->describeTable($this->getChildTable($object->getChildMode()));
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

    protected function _afterSave(\Magento\Framework\Model\AbstractModel $object)
    {
        /** @var ParentActiveRecordAbstract $object */

        $result = parent::_afterSave($object);

        if (!$object->isObjectCreatingState()) {
            return $result;
        }

        if (is_null($object->getChildMode())) {

            if ($object->getData('reload_on_create')) {
                $object->load($object->getId());
            }

            return $result;
        }

        $data = $object->getData();
        $data[$this->getChildPrimary($object->getChildMode())] = (int)$object->getData('id');
        $dataColumns = array_keys($data);

        /** @var \Ess\M2ePro\Model\ActiveRecord\Component\Parent\AbstractModel $object */
        $object->unsetData();
        $parentColumnsInfo = $this->getConnection()->describeTable($this->getMainTable());

        foreach($parentColumnsInfo as $columnInfo) {
            if (in_array($columnInfo['COLUMN_NAME'], $dataColumns)) {
                $object->setData($columnInfo['COLUMN_NAME'], $data[$columnInfo['COLUMN_NAME']]);
            }
        }

        /** @var \Ess\M2ePro\Model\ActiveRecord\Component\Child\AbstractModel $childObject */
        $childObject = $this->activeRecordFactory->getObject($this->getChildModel($object->getChildMode()));
        $childColumnsInfo = $this->getConnection()->describeTable($this->getChildTable($object->getChildMode()));

        foreach($childColumnsInfo as $columnInfo) {
            if (in_array($columnInfo['COLUMN_NAME'], $dataColumns)) {
                $childObject->setData($columnInfo['COLUMN_NAME'], $data[$columnInfo['COLUMN_NAME']]);
            }
        }

        $childObject->isObjectNew(true);
        $childObject->save();

        $childObject->setParentObject($object);
        $object->setChildObject($childObject);

        if ($object->getData('reload_on_create')) {
            $object->load($object->getId());
            $childObject->load($object->getId());
        }

        return $result;
    }

    //########################################
}
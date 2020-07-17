<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\ActiveRecord;

class Relation extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /** @var \Ess\M2ePro\Model\ActiveRecord\Relation */
    protected $relationModel;

    /** @var \Ess\M2ePro\Helper\Factory  */
    protected $helperFactory;

    /** @var \Ess\M2ePro\Model\ActiveRecord\Factory  */
    protected $activeRecordFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        $connectionName = null
    ) {
        $this->helperFactory = $helperFactory;
        $this->activeRecordFactory = $activeRecordFactory;
        parent::__construct($context, $connectionName);
    }

    public function _construct()
    {
        $this->_setResource('M2ePro');
        $this->_idFieldName = 'id';
    }

    //########################################

    public function setRelationModel(\Ess\M2ePro\Model\ActiveRecord\Relation $relationModel)
    {
        $this->relationModel = $relationModel;
    }

    //########################################

    public function getMainTable()
    {
        return $this->relationModel->getParentObject()->getResource()->getMainTable();
    }

    public function getSecondTable()
    {
        return $this->relationModel->getChildObject()->getResource()->getMainTable();
    }

    //########################################

    protected function _getLoadSelect($field, $value, $object)
    {
        /** @var \Ess\M2ePro\Model\ActiveRecord\Relation $object */

        $parentTable = $object->getParentObject()->getResource()->getMainTable();
        $childTable = $object->getChildObject()->getResource()->getMainTable();

        $primaryKey = $object->getParentObject()->getResource()->getIdFieldName();
        $foreignKey = $object->getRelationKey();

        $field  = $this->getConnection()->quoteIdentifier(sprintf('%s.%s', $parentTable, $field));
        return $this->getConnection()
                    ->select()
                    ->from($parentTable)
                    ->join($childTable, "`{$childTable}`.`{$foreignKey}` = `{$parentTable}`.`{$primaryKey}`")
                    ->where($field . '= ?', $value);
    }

    protected function _afterLoad(\Magento\Framework\Model\AbstractModel $object)
    {
        /** @var \Ess\M2ePro\Model\ActiveRecord\Relation $object */

        $object->getParentObject()->getResource()->unserializeFields($object->getParentObject());
        $object->getChildObject()->getResource()->unserializeFields($object->getChildObject());
    }

    public function save(\Magento\Framework\Model\AbstractModel $object)
    {
        /** @var \Ess\M2ePro\Model\ActiveRecord\Relation $object */
        if ($object->isDeleted()) {
            return $this->delete($object);
        }

        $this->_beforeSave($object);
        if ($object->getParentObject()->hasDataChanges()) {
            $object->getParentObject()->save();
        }

        if ($object->getChildObject()->hasDataChanges()) {
            if (null === $object->getParentObject()->getId()) {
                throw new \Ess\M2ePro\Model\Exception\Logic('Parent model doesn\'t exists.');
            }

            $object->getChildObject()
                   ->setData($object->getRelationKey(), $object->getParentObject()->getId())
                   ->save();
        }

        $this->_afterSave($object);
        return $this;
    }

    public function delete(\Magento\Framework\Model\AbstractModel $object)
    {
        /** @var \Ess\M2ePro\Model\ActiveRecord\Relation $object */
        $this->_beforeDelete($object);

        $object->getParentObject()->delete();
        $object->getChildObject()->delete();

        $this->_afterDelete($object);

        return $this;
    }

    //########################################

    /**
     * @param \Magento\Framework\Model\AbstractModel $model
     * @param $key
     * @return bool
     */
    public function isModelContainField(\Magento\Framework\Model\AbstractModel $model, $key)
    {
        $helper = $this->helperFactory->getObject('Data_Cache_Runtime');
        $ddlData = $helper->getValue(__METHOD__ . get_class($model));
        if (null === $ddlData) {
            $ddlData = $this->getConnection()->describeTable($model->getResource()->getMainTable());
            $helper->setValue(__METHOD__, $ddlData);
        }

        foreach (array_keys($ddlData) as $field) {
            if (strtolower($field) === strtolower($key)) {
                return true;
            }
        }

        return false;
    }

    //########################################
}

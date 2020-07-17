<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\ActiveRecord;

abstract class ActiveRecordAbstract extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Use is object new method for save of object
     * @var bool
     */
    protected $_useIsObjectNew = true;

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

    //########################################

    /**
     * @param $helperName
     * @param array $arguments
     * @return \Magento\Framework\App\Helper\AbstractHelper
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function getHelper($helperName, array $arguments = [])
    {
        return $this->helperFactory->getObject($helperName, $arguments);
    }

    //########################################

    protected function _beforeSave(\Magento\Framework\Model\AbstractModel $object)
    {
        /** @var \Ess\M2ePro\Model\ActiveRecord\ActiveRecordAbstract $object */

        if (null === $object->getId()) {
            $object->setData('create_date', $this->getHelper('Data')->getCurrentGmtDate());
        }

        $object->setData('update_date', $this->getHelper('Data')->getCurrentGmtDate());

        $result = parent::_beforeSave($object);

        // fix for \Magento\Framework\DB\Adapter\Pdo\Mysql::prepareColumnValue
        // an empty string cannot be saved -> NULL is saved instead
        foreach ($object->getData() as $key => $value) {
            $value === '' && $object->setData($key, new \Zend_Db_Expr("''"));
        }

        return $result;
    }

    protected function _afterSave(\Magento\Framework\Model\AbstractModel $object)
    {
        /** @var \Ess\M2ePro\Model\ActiveRecord\ActiveRecordAbstract $object */

        // fix for \Magento\Framework\DB\Adapter\Pdo\Mysql::prepareColumnValue
        // an empty string cannot be saved -> NULL is saved instead
        foreach ($object->getData() as $key => $value) {
            if ($value instanceof \Zend_Db_Expr && $value->__toString() === '\'\'') {
                $object->setData($key, '');
            }
        }

        return parent::_afterSave($object);
    }

    //########################################
}

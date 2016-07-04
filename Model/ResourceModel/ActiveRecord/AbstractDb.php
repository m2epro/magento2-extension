<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\ActiveRecord;

abstract class AbstractDb extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected $helperFactory;
    protected $activeRecordFactory;
    protected $parentFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory,
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        $connectionName = null
    )
    {
        $this->helperFactory = $helperFactory;
        $this->activeRecordFactory = $activeRecordFactory;
        $this->parentFactory = $parentFactory;
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
        $origData = $object->getOrigData();

        if (empty($origData)) {
            $object->setData('create_date', $this->getHelper('Data')->getCurrentGmtDate());
        }

        $object->setData('update_date', $this->getHelper('Data')->getCurrentGmtDate());

        $result = parent::_beforeSave($object);

        // TODO test it on magento 2.0
        // fix for Varien\Db\Adapter\Pdo\Mysql::prepareColumnValue
        // an empty string cannot be saved -> NULL is saved instead
        // for Magento version > 1.6.x.x
        foreach ($object->getData() as $key => $value) {
            $value === '' && $object->setData($key,new \Zend_Db_Expr("''"));
        }

        return $result;
    }

    protected function _afterSave(\Magento\Framework\Model\AbstractModel $object)
    {
        // TODO test it on magento 2.0
        // fix for Varien\Db\Adapter\Pdo\Mysql::prepareColumnValue
        // an empty string cannot be saved -> NULL is saved instead
        // for Magento version > 1.6.x.x
        foreach ($object->getData() as $key => $value) {
            if ($value instanceof \Zend_Db_Expr && $value->__toString() === '\'\'') {
                $object->setData($key,'');
            }
        }

        return parent::_afterSave($object);
    }

    //########################################
}
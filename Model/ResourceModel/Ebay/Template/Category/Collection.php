<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Ebay\Template\Category;

use Magento\Framework\DB\Select;

/**
 * Class \Ess\M2ePro\Model\ResourceModel\Ebay\Template\Category\Collection
 */
class Collection extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Collection\AbstractModel
{
    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init(
            'Ess\M2ePro\Model\Ebay\Template\Category',
            'Ess\M2ePro\Model\ResourceModel\Ebay\Template\Category'
        );
    }

    //########################################

    /**
     * GroupBy fix
     */
    public function getSelectCountSql()
    {
        $sql = parent::getSelectCountSql();
        $sql->reset(\Zend_Db_Select::GROUP);
        return $sql;
    }

    //########################################
}

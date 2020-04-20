<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel;

/**
 * Class \Ess\M2ePro\Model\ResourceModel\Listing
 */
class Listing extends ActiveRecord\Component\Parent\AbstractModel
{
    //########################################

    public function _construct()
    {
        $this->_init('m2epro_listing', 'id');
    }

    //########################################

    public function updateStatisticColumns()
    {
        $listingProductTable = $this->activeRecordFactory->getObject('Listing\Product')->getResource()->getMainTable();

        $totalCountSelect = $this->getConnection()
                                 ->select()
                                 ->from($listingProductTable, new \Zend_Db_Expr('COUNT(*)'))
                                 ->where("`listing_id` = `{$this->getMainTable()}`.`id`");

        $activeCountSelect = $this->getConnection()
                                  ->select()
                                  ->from($listingProductTable, new \Zend_Db_Expr('COUNT(*)'))
                                  ->where("`listing_id` = `{$this->getMainTable()}`.`id`")
                                  ->where("`status` = ?", (int)\Ess\M2ePro\Model\Listing\Product::STATUS_LISTED);

        $inactiveCountSelect = $this->getConnection()
                                    ->select()
                                    ->from($listingProductTable, new \Zend_Db_Expr('COUNT(*)'))
                                    ->where("`listing_id` = `{$this->getMainTable()}`.`id`")
                                    ->where("`status` != ?", (int)\Ess\M2ePro\Model\Listing\Product::STATUS_LISTED);

        $query = "UPDATE `{$this->getMainTable()}`
                  SET `products_total_count` = (".$totalCountSelect->__toString()."),
                      `products_active_count` = (".$activeCountSelect->__toString()."),
                      `products_inactive_count` = (".$inactiveCountSelect->__toString().")";

        $this->getConnection()->query($query);
    }

    //########################################
}

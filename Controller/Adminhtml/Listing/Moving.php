<?php
/**
 * Created by PhpStorm.
 * User: myown
 * Date: 07.04.16
 * Time: 12:52
 */

namespace Ess\M2ePro\Controller\Adminhtml\Listing;

abstract class Moving extends \Ess\M2ePro\Controller\Adminhtml\Listing
{
    //########################################

    protected function productCanBeMoved($productId, $listing) {

        if ($listing->isComponentModeEbay()) {
            return !$listing->hasProduct($productId);
        }

        // Add attribute set filter
        // ---------------------------------------
        $table = $this->resourceConnection->getTableName('catalog_product_entity');
        $dbSelect = $this->resourceConnection->getConnection()
            ->select()
            ->from($table,new \Zend_Db_Expr('DISTINCT `entity_id`'))
            ->where('`entity_id` = ?',(int)$productId);

        $productArray = $this->resourceConnection->getConnection()->fetchCol($dbSelect);

        if (count($productArray) <= 0) {
            return false;
        }

        return true;
    }

    //########################################
}
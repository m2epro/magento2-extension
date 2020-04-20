<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Listing;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Listing\Moving
 */
abstract class Moving extends \Ess\M2ePro\Controller\Adminhtml\Listing
{
    //########################################

    protected function productCanBeMoved($productId, $listing)
    {

        if ($listing->isComponentModeEbay()) {
            return !$listing->hasProduct($productId);
        }

        // Add attribute set filter
        // ---------------------------------------
        $table = $this->getHelper('Module_Database_Structure')->getTableNameWithPrefix('catalog_product_entity');
        $dbSelect = $this->resourceConnection->getConnection()
            ->select()
            ->from($table, new \Zend_Db_Expr('DISTINCT `entity_id`'))
            ->where('`entity_id` = ?', (int)$productId);

        $productArray = $this->resourceConnection->getConnection()->fetchCol($dbSelect);

        if (count($productArray) <= 0) {
            return false;
        }

        return true;
    }

    //########################################
}

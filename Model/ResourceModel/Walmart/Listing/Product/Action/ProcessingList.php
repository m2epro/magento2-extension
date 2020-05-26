<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Walmart\Listing\Product\Action;

use \Ess\M2ePro\Model\Walmart\Listing\Product\Action\ProcessingList as ProcessingListModel;

/**
 * Class \Ess\M2ePro\Model\ResourceModel\Walmart\Listing\Product\Action\ProcessingList
 */
class ProcessingList extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\AbstractModel
{
    //########################################

    public function _construct()
    {
        $this->_init('m2epro_walmart_listing_product_action_processing_list', 'id');
    }

    //########################################

    public function markAsRelistInventoryReady($listingProductsIds)
    {
        $this->getConnection()->update(
            $this->getMainTable(),
            [
                'stage' => ProcessingListModel::STAGE_RELIST_INVENTORY_READY,
            ],
            ['listing_product_id IN (?)' => $listingProductsIds]
        );

        return $this;
    }

    public function markAsRelistInventoryWaitingResult($listingProductsIds, $requestPendingSingleId)
    {
        $this->getConnection()->update(
            $this->getMainTable(),
            [
                'stage' => ProcessingListModel::STAGE_RELIST_INVENTORY_WAITING_RESULT,
                'relist_request_pending_single_id' => $requestPendingSingleId,
            ],
            ['listing_product_id IN (?)' => $listingProductsIds]
        );

        return $this;
    }

    //########################################

    public function getUniqueRelistRequestPendingSingleIds()
    {
        $select = $this->getConnection()
            ->select()
            ->distinct(true)
            ->from(
                $this->getMainTable(),
                new \Zend_Db_Expr('DISTINCT `relist_request_pending_single_id`')
            )
            ->where('relist_request_pending_single_id IS NOT NULL')
            ->where('stage = ?', ProcessingListModel::STAGE_RELIST_INVENTORY_WAITING_RESULT);

        return $this->getConnection()->fetchCol($select);
    }

    //########################################
}

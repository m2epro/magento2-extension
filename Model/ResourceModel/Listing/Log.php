<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Listing;

/**
 * Class \Ess\M2ePro\Model\ResourceModel\Listing\Log
 */
class Log extends \Ess\M2ePro\Model\ResourceModel\Log\AbstractModel
{
    //########################################

    public function _construct()
    {
        $this->_init('m2epro_listing_log', 'id');
    }

    /**
     * @return string
     */
    public function getConfigGroupSuffix()
    {
        return 'listings';
    }

    //########################################

    public function updateListingTitle($listingId, $title)
    {
        if ($title == '') {
            return false;
        }

        $this->getConnection()->update(
            $this->getMainTable(),
            ['listing_title'=>$title],
            ['listing_id = ?'=>(int)$listingId]
        );

        return true;
    }

    public function updateProductTitle($productId, $title)
    {
        if ($title == '') {
            return false;
        }

        $this->getConnection()->update(
            $this->getMainTable(),
            ['product_title'=>$title],
            ['product_id = ?'=>(int)$productId]
        );

        return true;
    }

    public function getStatusByActionId($listingLog, $actionId)
    {
        /** @var \Ess\M2ePro\Model\Listing\Log $listingLog*/
        $collection = $listingLog->getCollection();
        $collection->addFieldToFilter('action_id', $actionId);
        $collection->addOrder('type');
        $resultType = $collection->getFirstItem()->getData('type');

        if (empty($resultType)) {
            throw new \Exception('Logs action ID does not exist.');
        }

        return $this->getHelper('Module\Log')->getStatusByResultType($resultType);
    }

    //########################################
}

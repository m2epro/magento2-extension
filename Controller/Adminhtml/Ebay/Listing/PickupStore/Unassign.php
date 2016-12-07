<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\PickupStore;

class Unassign extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\PickupStore
{
    //########################################

    public function execute()
    {
        $listingProductPickupStoreIds = explode(',', $this->getRequest()->getParam('selected_products'));

        if (empty($listingProductPickupStoreIds)) {
            $this->setJsonContent(['result'=>'warning','action_id'=>0]);
            return $this->getResult();
        }

        if (!is_array($listingProductPickupStoreIds)) {
            $listingProductPickupStoreIds = [$listingProductPickupStoreIds];
        }

        $this->markInventoryForDelete($listingProductPickupStoreIds);

        $tableEbayListingProductPickupStore = $this->activeRecordFactory->getObject(
            'Ebay\Listing\Product\PickupStore'
        )->getResource()->getMainTable();
        $this->resourceConnection->getConnection()->delete($tableEbayListingProductPickupStore,
            '`id` IN ('.implode(',', $listingProductPickupStoreIds).')'
        );

        $this->setJsonContent(['result'=>'success','action_id'=>0]);
        return $this->getResult();
    }

    // ---------------------------------------

    protected function markInventoryForDelete(array $listingProductPickupStoreIds)
    {
        if (empty($listingProductPickupStoreIds)) {
            return false;
        }

        $collection = $this->activeRecordFactory->getObject('Ebay\Listing\Product\PickupStore')->getCollection();
        $collection->addFieldToFilter('main_table.id', ['in' => $listingProductPickupStoreIds]);
        $collection->getSelect()->join(
            ['elp' => $this->activeRecordFactory->getObject('Ebay\Listing\Product')->getResource()->getMainTable()],
            'elp.listing_product_id=main_table.listing_product_id',
            ['online_sku' => 'online_sku']
        );
        $collection->getSelect()->joinLeft(
            ['lpv' => $this->activeRecordFactory->getObject('Listing\Product\Variation')
                                                ->getResource()->getMainTable()],
            'lpv.listing_product_id=elp.listing_product_id',
            ['id']
        );
        $collection->getSelect()->joinLeft(
            ['elpv' => $this->activeRecordFactory->getObject('Ebay\Listing\Product\Variation')
                                                 ->getResource()->getMainTable()],
            'elpv.listing_product_variation_id=lpv.id',
            ['variation_online_sku' => 'online_sku']
        );
        $collection->getSelect()->joinLeft(
            ['meapss' => $this->activeRecordFactory->getObject('Ebay\Account\PickupStore\State')
                                                   ->getResource()->getMainTable()],
            'meapss.account_pickup_store_id = main_table.account_pickup_store_id
                 AND (meapss.sku = elp.online_sku OR meapss.sku = elpv.online_sku)',
            ['state_id' => 'id']
        );

        $stmtTemp = $this->resourceConnection->getConnection()->query($collection->getSelect()->__toString());

        $idsForDelete = [];
        while ($row = $stmtTemp->fetch(\PDO::FETCH_ASSOC)) {
            !empty($row['state_id']) && $idsForDelete[] = $row['state_id'];
        }

        if (empty($idsForDelete)) {
            return false;
        }

        $this->resourceConnection->getConnection()->update(
            $this->activeRecordFactory->getObject('Ebay\Account\PickupStore\State')->getResource()->getMainTable(),
            ['is_deleted' => 1],
            '`id` IN ('.implode(',', $idsForDelete).')'
        );
    }

    //########################################
}
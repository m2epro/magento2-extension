<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Product;

use \Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Product\PickupStore\CollectionFactory;

/**
 * Class \Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Product\PickupStore
 */
class PickupStore extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\AbstractModel
{
    protected $pickupStoreCollectionFactory;

    //########################################

    public function __construct(
        CollectionFactory $pickupStoreCollectionFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory,
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        $connectionName = null
    ) {
        $this->pickupStoreCollectionFactory = $pickupStoreCollectionFactory;
        parent::__construct($helperFactory, $activeRecordFactory, $parentFactory, $context, $connectionName);
    }

    //########################################

    public function _construct()
    {
        $this->_init('m2epro_ebay_listing_product_pickup_store', 'id');
    }

    //########################################

    public function assignProductsToStores(array $productsIds, array $storesIds)
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Product\PickupStore\Collection $collection */
        $collection = $this->pickupStoreCollectionFactory->create();
        $collection->addFieldToFilter('listing_product_id', ['in' => $productsIds]);
        $collection->addFieldToFilter('account_pickup_store_id', ['in' => $storesIds]);

        $existData = [];
        foreach ($collection as $existItem) {
            $key = $existItem['listing_product_id'] . '|' . $existItem['account_pickup_store_id'];
            $existData[$key] = true;
        }

        $insertData = [];
        foreach ($productsIds as $productId) {
            foreach ($storesIds as $storeId) {
                $key = $productId . '|' . $storeId;
                if (isset($existData[$key])) {
                    continue;
                }

                $insertData[] = [
                    'listing_product_id'      => $productId,
                    'account_pickup_store_id' => $storeId,
                    'is_process_required'     => 1,
                ];
            }
        }

        if (empty($insertData)) {
            return;
        }

        $this->getConnection()->insertMultiple(
            $this->activeRecordFactory->getObject('Ebay_Listing_Product_PickupStore')->getResource()->getMainTable(),
            $insertData
        );
    }

    //########################################

    public function processDeletedProduct(\Ess\M2ePro\Model\Listing\Product $listingProduct)
    {
        /** @var \Ess\M2ePro\Model\Ebay\Listing\Product $ebayListingProduct */
        $ebayListingProduct = $listingProduct->getChildObject();
        $onlineSku = $ebayListingProduct->getOnlineSku();

        if (!empty($onlineSku)) {
            /** @var \Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Product\PickupStore\Collection $collection */
            $collection = $this->pickupStoreCollectionFactory->create();
            $collection->addFieldToFilter('listing_product_id', $listingProduct->getId());

            $usedPickupStoresIds = $collection->getColumnValues('account_pickup_store_id');
            if (empty($usedPickupStoresIds)) {
                return;
            }

            $this->getConnection()->update(
                $this->activeRecordFactory->getObject('Ebay_Account_PickupStore_State')->getResource()->getMainTable(),
                ['is_deleted' => 1],
                ['sku = ?' => $onlineSku, 'account_pickup_store_id IN (?)' => $usedPickupStoresIds]
            );
        }

        $this->getConnection()->delete(
            $this->activeRecordFactory->getObject('Ebay_Listing_Product_PickupStore')->getResource()->getMainTable(),
            ['listing_product_id = ?' => $listingProduct->getId()]
        );
    }

    public function processDeletedVariation(\Ess\M2ePro\Model\Listing\Product\Variation $variation)
    {
        /** @var \Ess\M2ePro\Model\Ebay\Listing\Product\Variation $ebayVariation */
        $ebayVariation = $variation->getChildObject();
        $onlineSku = $ebayVariation->getOnlineSku();

        if (empty($onlineSku)) {
            return;
        }

        /** @var \Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Product\PickupStore\Collection $pickupStoreCollection */
        $pickupStoreCollection = $this->pickupStoreCollectionFactory->create();
        $pickupStoreCollection->addFieldToFilter('listing_product_id', $variation->getListingProductId());

        $usedPickupStoresIds = $pickupStoreCollection->getColumnValues('account_pickup_store_id');
        if (empty($usedPickupStoresIds)) {
            return;
        }

        $this->getConnection()->update(
            $this->activeRecordFactory->getObject('Ebay_Account_PickupStore_State')->getResource()->getMainTable(),
            ['is_deleted' => 1],
            ['sku = ?' => $onlineSku, 'account_pickup_store_id IN (?)' => $usedPickupStoresIds]
        );
    }

    //########################################
}

<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Observer\Amazon\Order;

class Item extends \Ess\M2ePro\Model\Observer\AbstractModel
{
    protected $amazonFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    )
    {
        $this->amazonFactory = $amazonFactory;
        parent::__construct($helperFactory, $activeRecordFactory, $modelFactory);
    }

    //########################################

    public function process()
    {
        /** @var \Ess\M2ePro\Model\Order\Item $orderItem */
        $orderItem  = $this->getEvent()->getData('order_item');

        /** @var \Ess\M2ePro\Model\Amazon\Order\Item $amazonOrderItem */
        $amazonOrderItem = $orderItem->getChildObject();

        /** @var \Magento\Catalog\Model\Product $product */
        $product = $this->getEvent()->getData('product');

        $listingOtherCollection = $this->amazonFactory->getObject('Listing\Other')->getCollection();
        $listingOtherCollection->addFieldToFilter('account_id', $orderItem->getOrder()->getAccountId());
        $listingOtherCollection->addFieldToFilter('second_table.sku', $amazonOrderItem->getSku());

        $otherListings = $listingOtherCollection->getItems();

        if (!empty($otherListings)) {
            /** @var \Ess\M2ePro\Model\Listing\Other $otherListing */
            $otherListing = reset($otherListings);

            if (!is_null($otherListing->getProductId())) {
                return;
            }

            $otherListing->mapProduct($product->getId(), \Ess\M2ePro\Helper\Data::INITIATOR_EXTENSION);
        } else {
            $dataForAdd = array(
                'account_id'     => $orderItem->getOrder()->getAccountId(),
                'marketplace_id' => $orderItem->getOrder()->getMarketplaceId(),
                'sku'            => $amazonOrderItem->getSku(),
                'product_id'     => $product->getId(),
                'store_id'       => $amazonOrderItem->getAmazonOrder()->getAssociatedStoreId(),
            );

            $this->activeRecordFactory->getObject('Amazon\Item')->setData($dataForAdd)->save();
        }
    }

    //########################################
}
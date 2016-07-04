<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Observer\Ebay\Order;

class Item extends \Ess\M2ePro\Model\Observer\AbstractModel
{
    protected $ebayFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    )
    {
        $this->ebayFactory = $ebayFactory;
        parent::__construct($helperFactory, $activeRecordFactory, $modelFactory);
    }

    //########################################

    public function process()
    {
        /** @var \Ess\M2ePro\Model\Order\Item $orderItem */
        $orderItem  = $this->getEvent()->getData('order_item');

        /** @var \Ess\M2ePro\Model\Ebay\Order\Item $ebayOrderItem */
        $ebayOrderItem = $orderItem->getChildObject();

        /** @var \Magento\Catalog\Model\Product $product */
        $product = $this->getEvent()->getData('product');

        $listingOtherCollection = $this->ebayFactory->getObject('Listing\Other')->getCollection();
        $listingOtherCollection->addFieldToFilter('account_id', $orderItem->getOrder()->getAccountId());
        $listingOtherCollection->addFieldToFilter('marketplace_id', $orderItem->getOrder()->getMarketplaceId());
        $listingOtherCollection->addFieldToFilter('second_table.item_id', $ebayOrderItem->getItemId());

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
                'item_id'        => $ebayOrderItem->getItemId(),
                'product_id'     => $product->getId(),
                'store_id'       => $ebayOrderItem->getEbayOrder()->getAssociatedStoreId(),
            );

            $this->activeRecordFactory->getObject('Ebay\Item')->setData($dataForAdd)->save();
        }
    }

    //########################################
}
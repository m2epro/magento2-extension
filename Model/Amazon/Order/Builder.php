<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Order;

use Ess\M2ePro\Model\AbstractModel;

/**
 * Class \Ess\M2ePro\Model\Amazon\Order\Builder
 */
class Builder extends AbstractModel
{
    const INSTRUCTION_INITIATOR = 'order_builder';

    const STATUS_NOT_MODIFIED = 0;
    const STATUS_NEW          = 1;
    const STATUS_UPDATED      = 2;

    const UPDATE_STATUS = 'status';
    const UPDATE_EMAIL  = 'email';

    //########################################

    protected $activeRecordFactory;

    protected $amazonFactory;

    /** @var $order \Ess\M2ePro\Model\Account */
    protected $account = null;

    /** @var $order \Ess\M2ePro\Model\Order */
    protected $order = null;

    protected $status = self::STATUS_NOT_MODIFIED;

    protected $items = [];

    protected $updates = [];

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $data = []
    ) {
        $this->amazonFactory = $amazonFactory;
        $this->activeRecordFactory = $activeRecordFactory;
        parent::__construct($helperFactory, $modelFactory, $data);
    }

    //########################################

    public function initialize(\Ess\M2ePro\Model\Account $account, array $data = [])
    {
        $this->account = $account;

        $this->initializeData($data);
        $this->initializeOrder();
    }

    //########################################

    protected function initializeData(array $data = [])
    {
        // Init general data
        // ---------------------------------------
        $this->setData('account_id', $this->account->getId());
        $this->setData('amazon_order_id', $data['amazon_order_id']);
        $this->setData('seller_order_id', $data['seller_order_id']);
        $this->setData('marketplace_id', $data['marketplace_id']);

        $this->setData('status', $this->modelFactory->getObject('Amazon_Order_Helper')->getStatus($data['status']));
        $this->setData('is_afn_channel', $data['is_afn_channel']);
        $this->setData('is_prime', $data['is_prime']);
        $this->setData('is_business', $data['is_business']);

        $this->setData('purchase_update_date', $data['purchase_update_date']);
        $this->setData('purchase_create_date', $data['purchase_create_date']);
        // ---------------------------------------

        // Init sale data
        // ---------------------------------------
        $this->setData('paid_amount', (float)$data['paid_amount']);
        $this->setData('tax_details', $this->getHelper('Data')->jsonEncode($this->prepareTaxDetails($data)));
        $this->setData('ioss_number', $data['items'][0]['ioss_number']);
        $this->setData('discount_details', $this->getHelper('Data')->jsonEncode($data['discount_details']));
        $this->setData('currency', $data['currency']);
        $this->setData('qty_shipped', $data['qty_shipped']);
        $this->setData('qty_unshipped', $data['qty_unshipped']);
        // ---------------------------------------

        // Init customer/shipping data
        // ---------------------------------------
        $this->setData('buyer_name', $data['buyer_name']);
        $this->setData('buyer_email', $data['buyer_email']);
        $this->setData('shipping_service', $data['shipping_service']);
        $this->setData('shipping_address', $data['shipping_address']);
        $this->setData('shipping_price', (float)$data['shipping_price']);
        $this->setData('shipping_date_to', $data['shipping_date_to']);
        $this->setData('delivery_date_to', $data['delivery_date_to']);
        // ---------------------------------------

        $this->items = $data['items'];
    }

    //########################################

    protected function initializeOrder()
    {
        $this->status = self::STATUS_NOT_MODIFIED;

        $existOrders = $this->amazonFactory->getObject('Order')->getCollection()
            ->addFieldToFilter('account_id', $this->account->getId())
            ->addFieldToFilter('amazon_order_id', $this->getData('amazon_order_id'))
            ->setOrder('id', \Magento\Framework\Data\Collection::SORT_ORDER_DESC)
            ->getItems();
        $existOrdersNumber = count($existOrders);

        // duplicated M2ePro orders. remove m2e order without magento order id or newest order
        // ---------------------------------------
        if ($existOrdersNumber > 1) {
            $isDeleted = false;

            foreach ($existOrders as $key => $order) {
                /** @var \Ess\M2ePro\Model\Order $order */

                $magentoOrderId = $order->getMagentoOrderId();
                if (!empty($magentoOrderId)) {
                    continue;
                }

                $order->delete();
                unset($existOrders[$key]);
                $isDeleted = true;
                break;
            }

            if (!$isDeleted) {
                $orderForRemove = reset($existOrders);
                $orderForRemove->delete();
            }
        }
        // ---------------------------------------

        // New order
        // ---------------------------------------
        if ($existOrdersNumber == 0) {
            $this->status = self::STATUS_NEW;
            $this->order = $this->amazonFactory->getObject('Order');
            $this->order->setStatusUpdateRequired(true);

            return;
        }
        // ---------------------------------------

        // Already exist order
        // ---------------------------------------
        $this->order = reset($existOrders);
        $this->status = self::STATUS_UPDATED;

        if ($this->order->getMagentoOrderId() === null) {
            $this->order->setStatusUpdateRequired(true);
        }
        // ---------------------------------------
    }

    //########################################

    private function prepareTaxDetails($data)
    {
        if ($this->isNeedSkipTax($data)) {
            $data['tax_details']['product'] = 0;
            $data['tax_details']['shipping'] = 0;
            $data['tax_details']['gift'] = 0;
        }

        return $data['tax_details'];
    }

    protected function isNeedSkipTax($data)
    {
        if ($this->isSkipTaxForUS($data)) {
            return true;
        }

        if ($this->isSkipTaxForUkShipment($data)) {
            return true;
        }

        return false;
    }

    protected function isSkipTaxForUkShipment(array $data)
    {
        $countryCode = strtoupper($data['shipping_address']['country_code']);
        if (!in_array($countryCode, ['GB', 'UK'])) {
            return false;
        }

        if ($this->account->getChildObject()->isAmazonCollectsTaxForUKShipmentAvailable()) {
            return true;
        }

        if ($this->account->getChildObject()->isAmazonCollectsTaxForUKShipmentWithCertainPrice()
            && $this->isSumOfItemPriceLessThan135GBP(
                $data['items']
            )) {
            return true;
        }

        return false;
    }

    protected function isSkipTaxForUS(array $data)
    {
        if (!$this->account->getChildObject()->isAmazonCollectsEnabled()) {
            return false;
        }

        $statesList = $this->getHelper('Component\Amazon')->getStatesList();
        $excludedStates = $this->account->getChildObject()->getExcludedStates();

        if (empty($excludedStates) || !isset($data['shipping_address']['state'])) {
            return false;
        }

        $state = strtoupper($data['shipping_address']['state']);

        foreach ($statesList as $code => $title) {
            if (!in_array($code, $excludedStates)) {
                continue;
            }

            if ($state == $code || $state == strtoupper($title)) {
                return true;
            }
        }

        return false;
    }

    protected function calculateItemPrice(array $items)
    {
        $result = 0.0;

        foreach ($items as $item) {
            $result += $this->convertPricetoGBP($item['price'], trim($item['currency']));
        }

        return $result;
    }

    protected function convertPriceToGBP($price, $currency)
    {
        return $this->modelFactory->getObject('Currency')->convertPriceToCurrency($price, $currency, 'GBP');
    }

    protected function isSumOfItemPriceLessThan135GBP($items)
    {
        return $this->calculateItemPrice($items) < 135;
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Order
     */
    public function process()
    {
        $this->checkUpdates();

        $this->createOrUpdateOrder();
        $this->createOrUpdateItems();

        if ($this->isNew() && !$this->getData('is_afn_channel') &&
            $this->getData('status') != \Ess\M2ePro\Model\Amazon\Order::STATUS_CANCELED
        ) {
            $this->processListingsProductsUpdates();
            $this->processOtherListingsUpdates();
        }

        if ($this->isUpdated()) {
            $this->processMagentoOrderUpdates();
        }

        return $this->order;
    }

    //########################################

    protected function createOrUpdateItems()
    {
        $itemsCollection = $this->order->getItemsCollection();
        $itemsCollection->load();

        foreach ($this->items as $itemData) {
            $itemData['order_id'] = $this->order->getId();

            /** @var $itemBuilder \Ess\M2ePro\Model\Amazon\Order\Item\Builder */
            $itemBuilder = $this->modelFactory->getObject('Amazon_Order_Item_Builder');
            $itemBuilder->initialize($itemData);

            $item = $itemBuilder->process();
            $item->setOrder($this->order);

            $itemsCollection->removeItemByKey($item->getId());
            $itemsCollection->addItem($item);
        }
    }

    //########################################

    /**
     * @return bool
     */
    protected function isNew()
    {
        return $this->status == self::STATUS_NEW;
    }

    /**
     * @return bool
     */
    protected function isUpdated()
    {
        return $this->status == self::STATUS_UPDATED;
    }

    //########################################

    /**
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    private function createOrUpdateOrder()
    {
        if (!$this->isNew() && $this->getData('status') == \Ess\M2ePro\Model\Amazon\Order::STATUS_CANCELED) {
            $this->order->getChildObject()->setData('status', \Ess\M2ePro\Model\Amazon\Order::STATUS_CANCELED);
            $this->order->getChildObject()->setData('purchase_update_date', $this->getData('purchase_update_date'));
            $this->order->getChildObject()->save();
        } else {
            $this->setData(
                'shipping_address',
                $this->getHelper('Data')->jsonEncode($this->getData('shipping_address'))
            );

            foreach ($this->getData() as $key => $value) {
                if (!$this->order->getId() || ($this->order->hasData($key) && $this->order->getData($key) != $value)) {
                    $this->order->addData($this->getData());
                    $this->order->save();
                    break;
                }
            }

            $amazonOrder = $this->order->getChildObject();
            foreach ($this->getData() as $key => $value) {
                if (!$this->order->getId() || ($amazonOrder->hasData($key) && $amazonOrder->getData($key) != $value)) {
                    $amazonOrder->addData($this->getData());
                    $amazonOrder->save();
                    break;
                }
            }
        }

        $this->order->setAccount($this->account);

        if ($this->order->getChildObject()->isCanceled() && $this->order->getReserve()->isPlaced()) {
            $this->order->getReserve()->cancel();
        }
    }

    //########################################

    protected function checkUpdates()
    {
        if ($this->hasUpdatedStatus()) {
            $this->updates[] = self::UPDATE_STATUS;
        }
        if ($this->hasUpdatedEmail()) {
            $this->updates[] = self::UPDATE_EMAIL;
        }
    }

    protected function hasUpdatedStatus()
    {
        if (!$this->isUpdated()) {
            return false;
        }

        return $this->getData('status') != $this->order->getChildObject()->getData('status');
    }

    protected function hasUpdatedEmail()
    {
        if (!$this->isUpdated()) {
            return false;
        }

        $newEmail = $this->getData('buyer_email');
        $oldEmail = $this->order->getChildObject()->getData('buyer_email');

        if ($newEmail == $oldEmail) {
            return false;
        }

        return filter_var($newEmail, FILTER_VALIDATE_EMAIL) !== false;
    }

    //########################################

    protected function hasUpdates()
    {
        return !empty($this->updates);
    }

    protected function hasUpdate($update)
    {
        return in_array($update, $this->updates);
    }

    protected function processMagentoOrderUpdates()
    {
        if (!$this->hasUpdates() || $this->order->getMagentoOrder() === null) {
            return;
        }

        if ($this->hasUpdate(self::UPDATE_STATUS) && $this->order->getChildObject()->isCanceled()) {
            $this->cancelMagentoOrder();

            return;
        }

        /** @var $magentoOrderUpdater \Ess\M2ePro\Model\Magento\Order\Updater */
        $magentoOrderUpdater = $this->modelFactory->getObject('Magento_Order_Updater');
        $magentoOrderUpdater->setMagentoOrder($this->order->getMagentoOrder());

        if ($this->hasUpdate(self::UPDATE_STATUS)) {
            $this->order->setStatusUpdateRequired(true);

            $this->order->getProxy()->setStore($this->order->getStore());

            $shippingData = $this->order->getProxy()->getShippingData();
            $magentoOrderUpdater->updateShippingDescription(
                $shippingData['carrier_title'] . ' - ' . $shippingData['shipping_method']
            );
        }

        if ($this->hasUpdate(self::UPDATE_EMAIL)) {
            $magentoOrderUpdater->updateCustomerEmail($this->order->getChildObject()->getBuyerEmail());
        }

        $magentoOrderUpdater->finishUpdate();
    }

    protected function cancelMagentoOrder()
    {
        if (!$this->order->canCancelMagentoOrder()) {
            return;
        }

        $magentoOrderComments = [];
        $magentoOrderComments[] = '<b>Attention!</b> Order was canceled on Amazon.';

        try {
            $this->order->cancelMagentoOrder();
        } catch (\Exception $e) {
            $magentoOrderComments[] = 'Order cannot be canceled in Magento. Reason: ' . $e->getMessage();
        }

        /** @var $magentoOrderUpdater \Ess\M2ePro\Model\Magento\Order\Updater */
        $magentoOrderUpdater = $this->modelFactory->getObject('Magento_Order_Updater');
        $magentoOrderUpdater->setMagentoOrder($this->order->getMagentoOrder());
        $magentoOrderUpdater->updateComments($magentoOrderComments);
        $magentoOrderUpdater->finishUpdate();
    }

    //########################################

    protected function processListingsProductsUpdates()
    {
        $logger = $this->activeRecordFactory->getObject('Listing\Log');
        $logger->setComponentMode(\Ess\M2ePro\Helper\Component\Amazon::NICK);

        $logsActionId = $this->activeRecordFactory->getObject('Listing\Log')->getResource()->getNextActionId();

        $parentsForProcessing = [];

        foreach ($this->items as $orderItem) {
            /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection $listingProductCollection */
            $listingProductCollection = $this->amazonFactory->getObject('Listing\Product')->getCollection();
            $listingProductCollection->getSelect()->join(
                ['l' => $this->activeRecordFactory->getObject('Listing')->getResource()->getMainTable()],
                'main_table.listing_id=l.id',
                ['account_id']
            );
            $listingProductCollection->addFieldToFilter('sku', $orderItem['sku']);
            $listingProductCollection->addFieldToFilter('l.account_id', $this->account->getId());

            /** @var \Ess\M2ePro\Model\Listing\Product[] $listingsProducts */
            $listingsProducts = $listingProductCollection->getItems();
            if (empty($listingsProducts)) {
                continue;
            }

            foreach ($listingsProducts as $listingProduct) {
                if (!$listingProduct->isListed() && !$listingProduct->isStopped()) {
                    continue;
                }

                /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
                $amazonListingProduct = $listingProduct->getChildObject();

                if ($amazonListingProduct->isAfnChannel()) {
                    continue;
                }

                $currentOnlineQty = $amazonListingProduct->getOnlineQty();

                // if product was linked by sku during list action
                if ($listingProduct->isStopped() && $currentOnlineQty === null) {
                    continue;
                }

                $variationManager = $amazonListingProduct->getVariationManager();

                if ($variationManager->isRelationChildType()) {
                    $parentListingProduct = $variationManager->getTypeModel()->getParentListingProduct();
                    $parentsForProcessing[$parentListingProduct->getId()] = $parentListingProduct;
                }

                $instruction = $this->activeRecordFactory->getObject('Listing_Product_Instruction');
                $instruction->setData(
                    [
                        'listing_product_id' => $listingProduct->getId(),
                        'component'          => \Ess\M2ePro\Helper\Component\Amazon::NICK,
                        'type'               => \Ess\M2ePro\Model\Amazon\Listing\Product::INSTRUCTION_TYPE_CHANNEL_QTY_CHANGED,
                        'initiator'          => self::INSTRUCTION_INITIATOR,
                        'priority'           => 80,
                    ]
                );
                $instruction->save();

                if ($currentOnlineQty > $orderItem['qty_purchased']) {
                    $listingProduct->getChildObject()->setData(
                        'online_qty',
                        $currentOnlineQty - $orderItem['qty_purchased']
                    );

                    $tempLogMessage = $this->helperFactory->getObject('Module\Translation')->__(
                        'Item QTY was changed from %from% to %to% .',
                        $currentOnlineQty,
                        ($currentOnlineQty - $orderItem['qty_purchased'])
                    );

                    $logger->addProductMessage(
                        $listingProduct->getListingId(),
                        $listingProduct->getProductId(),
                        $listingProduct->getId(),
                        \Ess\M2ePro\Helper\Data::INITIATOR_EXTENSION,
                        $logsActionId,
                        \Ess\M2ePro\Model\Listing\Log::ACTION_CHANNEL_CHANGE,
                        $tempLogMessage,
                        \Ess\M2ePro\Model\Log\AbstractModel::TYPE_SUCCESS
                    );

                    $listingProduct->save();

                    continue;
                }

                $listingProduct->getChildObject()->setData('online_qty', 0);

                $tempLogMessages = [
                    $this->helperFactory->getObject('Module\Translation')->__(
                        'Item QTY was changed from %from% to %to% .',
                        empty($currentOnlineQty) ? '"empty"' : $currentOnlineQty,
                        0
                    )
                ];

                if (!$listingProduct->isStopped()) {
                    $statusChangedFrom = $this->getHelper('Component\Amazon')
                        ->getHumanTitleByListingProductStatus($listingProduct->getStatus());
                    $statusChangedTo = $this->getHelper('Component\Amazon')
                        ->getHumanTitleByListingProductStatus(\Ess\M2ePro\Model\Listing\Product::STATUS_STOPPED);

                    if (!empty($statusChangedFrom) && !empty($statusChangedTo)) {
                        $tempLogMessages[] = $this->helperFactory->getObject('Module\Translation')->__(
                            'Item Status was changed from "%from%" to "%to%" .',
                            $statusChangedFrom,
                            $statusChangedTo
                        );
                    }

                    $listingProduct->setData(
                        'status_changer',
                        \Ess\M2ePro\Model\Listing\Product::STATUS_CHANGER_COMPONENT
                    );
                    $listingProduct->setData('status', \Ess\M2ePro\Model\Listing\Product::STATUS_STOPPED);
                }

                foreach ($tempLogMessages as $tempLogMessage) {
                    $logger->addProductMessage(
                        $listingProduct->getListingId(),
                        $listingProduct->getProductId(),
                        $listingProduct->getId(),
                        \Ess\M2ePro\Helper\Data::INITIATOR_EXTENSION,
                        $logsActionId,
                        \Ess\M2ePro\Model\Listing\Log::ACTION_CHANNEL_CHANGE,
                        $tempLogMessage,
                        \Ess\M2ePro\Model\Log\AbstractModel::TYPE_SUCCESS
                    );
                }

                $listingProduct->save();
            }
        }

        if (!empty($parentsForProcessing)) {
            $massProcessor = $this->modelFactory->getObject(
                'Amazon_Listing_Product_Variation_Manager_Type_Relation_ParentRelation_Processor_Mass'
            );
            $massProcessor->setListingsProducts($parentsForProcessing);
            $massProcessor->execute();
        }
    }

    protected function processOtherListingsUpdates()
    {
        foreach ($this->items as $orderItem) {
            /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Other\Collection $listingOtherCollection */
            $listingOtherCollection = $this->amazonFactory->getObject('Listing\Other')->getCollection();
            $listingOtherCollection->addFieldToFilter('sku', $orderItem['sku']);
            $listingOtherCollection->addFieldToFilter('account_id', $this->account->getId());

            /** @var \Ess\M2ePro\Model\Listing\Other[] $otherListings */
            $otherListings = $listingOtherCollection->getItems();
            if (empty($otherListings)) {
                continue;
            }

            foreach ($otherListings as $otherListing) {
                if (!$otherListing->isListed() && !$otherListing->isStopped()) {
                    continue;
                }

                /** @var \Ess\M2ePro\Model\Amazon\Listing\Other $amazonOtherListing */
                $amazonOtherListing = $otherListing->getChildObject();

                if ($amazonOtherListing->isAfnChannel()) {
                    continue;
                }

                $currentOnlineQty = $amazonOtherListing->getOnlineQty();

                if ($currentOnlineQty > $orderItem['qty_purchased']) {
                    $amazonOtherListing->setData('online_qty', $currentOnlineQty - $orderItem['qty_purchased']);
                    $amazonOtherListing->save();

                    continue;
                }

                $amazonOtherListing->setData('online_qty', 0);

                if (!$otherListing->isStopped()) {
                    $otherListing->setData(
                        'status_changer',
                        \Ess\M2ePro\Model\Listing\Product::STATUS_CHANGER_COMPONENT
                    );
                    $otherListing->setData('status', \Ess\M2ePro\Model\Listing\Product::STATUS_STOPPED);
                }

                $otherListing->save();
            }
        }
    }

    //########################################
}

<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Order;

use Ess\M2ePro\Model\AbstractModel;

class Builder extends AbstractModel
{
    const STATUS_NOT_MODIFIED = 0;
    const STATUS_NEW          = 1;
    const STATUS_UPDATED      = 2;

    const UPDATE_STATUS = 'status';
    const UPDATE_EMAIL  = 'email';

    // M2ePro\TRANSLATIONS
    // Duplicated Amazon orders with ID #%id%.

    //########################################

    private $activeRecordFactory;

    private $amazonFactory;

    /** @var $order \Ess\M2ePro\Model\Account */
    private $account = NULL;

    /** @var $order \Ess\M2ePro\Model\Order */
    private $order = NULL;

    private $status = self::STATUS_NOT_MODIFIED;

    private $items = array();

    private $updates = array();

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $data = []
    )
    {
        $this->activeRecordFactory = $activeRecordFactory;
        $this->amazonFactory = $amazonFactory;
        parent::__construct($helperFactory, $modelFactory, $data);
    }

    //########################################

    public function initialize(\Ess\M2ePro\Model\Account $account, array $data = array())
    {
        $this->account = $account;

        $this->initializeData($data);
        $this->initializeOrder();
    }

    //########################################

    private function initializeData(array $data = array())
    {
        // Init general data
        // ---------------------------------------
        $this->setData('account_id', $this->account->getId());
        $this->setData('amazon_order_id', $data['amazon_order_id']);
        $this->setData('marketplace_id', $data['marketplace_id']);

        $this->setData('status', $this->modelFactory->getObject('Amazon\Order\Helper')->getStatus($data['status']));
        $this->setData('is_afn_channel', $data['is_afn_channel']);
        $this->setData('is_prime', $data['is_prime']);
        $this->setData('is_business', $data['is_business']);

        $this->setData('purchase_update_date', $data['purchase_update_date']);
        $this->setData('purchase_create_date', $data['purchase_create_date']);
        // ---------------------------------------

        // Init sale data
        // ---------------------------------------
        $this->setData('paid_amount', (float)$data['paid_amount']);
        $this->setData('tax_details', $this->getHelper('Data')->jsonEncode($data['tax_details']));
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
        $this->setData('shipping_dates', $this->getHelper('Data')->jsonEncode($data['shipping_dates']));
        // ---------------------------------------

        $this->items = $data['items'];
    }

    //########################################

    private function initializeOrder()
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

        if (is_null($this->order->getMagentoOrderId())) {
            $this->order->setStatusUpdateRequired(true);
        }
        // ---------------------------------------
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

    private function createOrUpdateItems()
    {
        $itemsCollection = $this->order->getItemsCollection();
        $itemsCollection->load();

        foreach ($this->items as $itemData) {
            $itemData['order_id'] = $this->order->getId();

            /** @var $itemBuilder \Ess\M2ePro\Model\Amazon\Order\Item\Builder */
            $itemBuilder = $this->modelFactory->getObject('Amazon\Order\Item\Builder');
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
    private function isNew()
    {
        return $this->status == self::STATUS_NEW;
    }

    /**
     * @return bool
     */
    private function isUpdated()
    {
        return $this->status == self::STATUS_UPDATED;
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Order
     */
    private function createOrUpdateOrder()
    {
        if (!$this->isNew() && $this->getData('status') == \Ess\M2ePro\Model\Amazon\Order::STATUS_CANCELED) {
            $this->order->getChildObject()->setData('status', \Ess\M2ePro\Model\Amazon\Order::STATUS_CANCELED);
            $this->order->getChildObject()->setData('purchase_update_date', $this->getData('purchase_update_date'));
            $this->order->getChildObject()->save();
        } else {
            $this->setData('shipping_address', $this->getHelper('Data')->jsonEncode(
                $this->getData('shipping_address')
            ));
            $this->order->addData($this->getData());
            $this->order->save();

            $this->order->getChildObject()->addData($this->getData());
            $this->order->getChildObject()->save();
        }

        $this->order->setAccount($this->account);

        if ($this->order->getChildObject()->isCanceled() && $this->order->getReserve()->isPlaced()) {
            $this->order->getReserve()->cancel();
        }
    }

    //########################################

    private function checkUpdates()
    {
        if ($this->hasUpdatedStatus()) {
            $this->updates[] = self::UPDATE_STATUS;
        }
        if ($this->hasUpdatedEmail()) {
            $this->updates[] = self::UPDATE_EMAIL;
        }
    }

    private function hasUpdatedStatus()
    {
        if (!$this->isUpdated()) {
            return false;
        }

        return $this->getData('status') != $this->order->getData('status');
    }

    private function hasUpdatedEmail()
    {
        if (!$this->isUpdated()) {
            return false;
        }

        $newEmail = $this->getData('buyer_email');
        $oldEmail = $this->order->getData('buyer_email');

        if ($newEmail == $oldEmail) {
            return false;
        }

        return filter_var($newEmail, FILTER_VALIDATE_EMAIL) !== false;
    }

    //########################################

    private function hasUpdates()
    {
        return !empty($this->updates);
    }

    private function hasUpdate($update)
    {
        return in_array($update, $this->updates);
    }

    private function processMagentoOrderUpdates()
    {
        if (!$this->hasUpdates() || is_null($this->order->getMagentoOrder())) {
            return;
        }

        if ($this->hasUpdate(self::UPDATE_STATUS) && $this->order->getChildObject()->isCanceled()) {
            $this->cancelMagentoOrder();
            return;
        }

        /** @var $magentoOrderUpdater \Ess\M2ePro\Model\Magento\Order\Updater */
        $magentoOrderUpdater = $this->modelFactory->getObject('Magento\Order\Updater');
        $magentoOrderUpdater->setMagentoOrder($this->order->getMagentoOrder());

        if ($this->hasUpdate(self::UPDATE_STATUS)) {
            $this->order->setStatusUpdateRequired(true);

            $this->order->getProxy()->setStore($this->order->getStore());

            $shippingData = $this->order->getProxy()->getShippingData();
            $magentoOrderUpdater->updateShippingDescription(
                $shippingData['carrier_title'].' - '.$shippingData['shipping_method']
            );
        }

        if ($this->hasUpdate(self::UPDATE_EMAIL)) {
            $magentoOrderUpdater->updateCustomerEmail($this->order->getChildObject()->getBuyerEmail());
        }

        $magentoOrderUpdater->finishUpdate();
    }

    private function cancelMagentoOrder()
    {
        if (!$this->order->canCancelMagentoOrder()) {
            return;
        }

        $magentoOrderComments = array();
        $magentoOrderComments[] = '<b>Attention!</b> Order was canceled on Amazon.';

        try {
            $this->order->cancelMagentoOrder();
        } catch (\Exception $e) {
            $magentoOrderComments[] = 'Order cannot be canceled in Magento. Reason: ' . $e->getMessage();
        }

        /** @var $magentoOrderUpdater \Ess\M2ePro\Model\Magento\Order\Updater */
        $magentoOrderUpdater = $this->modelFactory->getObject('Magento\Order\Updater');
        $magentoOrderUpdater->setMagentoOrder($this->order->getMagentoOrder());
        $magentoOrderUpdater->updateComments($magentoOrderComments);
        $magentoOrderUpdater->finishUpdate();
    }

    //########################################

    private function processListingsProductsUpdates()
    {
        $logger = $this->activeRecordFactory->getObject('Listing\Log');
        $logger->setComponentMode(\Ess\M2ePro\Helper\Component\Amazon::NICK);

        $logsActionId = $this->activeRecordFactory->getObject('Listing\Log')->getResource()->getNextActionId();

        $parentsForProcessing = array();
        $listingsProductsIdsForNeedSynchRulesCheck = array();

        foreach ($this->items as $orderItem) {
            /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection $listingProductCollection */
            $listingProductCollection = $this->amazonFactory->getObject('Listing\Product')->getCollection();
            $listingProductCollection->getSelect()->join(
                array('l' => $this->activeRecordFactory->getObject('Listing')->getResource()->getMainTable()),
                'main_table.listing_id=l.id',
                array('account_id')
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
                if ($listingProduct->isStopped() && is_null($currentOnlineQty)) {
                    continue;
                }

                $variationManager = $amazonListingProduct->getVariationManager();

                if ($variationManager->isRelationChildType()) {
                    $parentListingProduct = $variationManager->getTypeModel()->getParentListingProduct();
                    $parentsForProcessing[$parentListingProduct->getId()] = $parentListingProduct;
                }

                $this->activeRecordFactory->getObject('ProductChange')->addUpdateAction(
                    $listingProduct->getProductId(),\Ess\M2ePro\Model\ProductChange::INITIATOR_SYNCHRONIZATION
                );

                if ($listingProduct->isSetProcessingLock('in_action')) {
                    $listingsProductsIdsForNeedSynchRulesCheck[] = $listingProduct->getId();
                }

                if ($currentOnlineQty > $orderItem['qty_purchased']) {
                    $listingProduct->setData('online_qty', $currentOnlineQty - $orderItem['qty_purchased']);

                    // M2ePro\TRANSLATIONS
                    // Item QTY was successfully changed from %from% to %to% .
                    $tempLogMessage = $this->helperFactory->getObject('Module\Translation')->__(
                        'Item QTY was successfully changed from %from% to %to% .',
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
                        \Ess\M2ePro\Model\Log\AbstractModel::TYPE_SUCCESS,
                        \Ess\M2ePro\Model\Log\AbstractModel::PRIORITY_LOW
                    );

                    $listingProduct->save();

                    continue;
                }

                $listingProduct->setData('online_qty', 0);

                $tempLogMessages = array($this->helperFactory->getObject('Module\Translation')->__(
                    'Item QTY was successfully changed from %from% to %to% .',
                    empty($currentOnlineQty) ? '"empty"' : $currentOnlineQty,
                    0
                ));

                if (!$listingProduct->isStopped()) {
                    $statusChangedFrom = $this->helperFactory->getObject('Component\Amazon')
                        ->getHumanTitleByListingProductStatus($listingProduct->getStatus());
                    $statusChangedTo = $this->helperFactory->getObject('Component\Amazon')
                        ->getHumanTitleByListingProductStatus(\Ess\M2ePro\Model\Listing\Product::STATUS_STOPPED);

                    if (!empty($statusChangedFrom) && !empty($statusChangedTo)) {
                        // M2ePro\TRANSLATIONS
                        // Item Status was successfully changed from "%from%" to "%to%" .
                        $tempLogMessages[] = $this->helperFactory->getObject('Module\Translation')->__(
                            'Item Status was successfully changed from "%from%" to "%to%" .',
                            $statusChangedFrom,
                            $statusChangedTo
                        );
                    }

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
                        \Ess\M2ePro\Model\Log\AbstractModel::TYPE_SUCCESS,
                        \Ess\M2ePro\Model\Log\AbstractModel::PRIORITY_LOW
                    );
                }

                $listingProduct->save();
            }
        }

        if (!empty($parentsForProcessing)) {
            $massProcessor = $this->modelFactory->getObject(
                'Amazon\Listing\Product\Variation\Manager\Type\Relation\ParentRelation\Processor\Mass'
            );
            $massProcessor->setListingsProducts($parentsForProcessing);
            $massProcessor->execute();
        }

        if (!empty($listingsProductsIdsForNeedSynchRulesCheck)) {
            $this->activeRecordFactory->getObject('Listing\Product')
                                      ->getResource()
                                      ->setNeedSynchRulesCheck(
                                          array_unique($listingsProductsIdsForNeedSynchRulesCheck)
                                      );
        }
    }

    private function processOtherListingsUpdates()
    {
        $logger = $this->activeRecordFactory->getObject('Listing\Other\Log');
        $logger->setComponentMode(\Ess\M2ePro\Helper\Component\Amazon::NICK);

        $logsActionId = $this->activeRecordFactory->getObject('Listing\Other\Log')->getResource()->getNextActionId();

        foreach ($this->items as $orderItem) {
            /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection $listingOtherCollection */
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
                    $otherListing->setData('online_qty', $currentOnlineQty - $orderItem['qty_purchased']);

                    // M2ePro\TRANSLATIONS
                    // Item QTY was successfully changed from %from% to %to% .
                    $tempLogMessage = $this->helperFactory->getObject('Module\Translation')->__(
                        'Item QTY was successfully changed from %from% to %to% .',
                        $currentOnlineQty,
                        ($currentOnlineQty - $orderItem['qty_purchased'])
                    );

                    $logger->addProductMessage(
                        $otherListing->getId(),
                        \Ess\M2ePro\Helper\Data::INITIATOR_EXTENSION,
                        $logsActionId,
                        \Ess\M2ePro\Model\Listing\Other\Log::ACTION_CHANNEL_CHANGE,
                        $tempLogMessage,
                        \Ess\M2ePro\Model\Log\AbstractModel::TYPE_SUCCESS,
                        \Ess\M2ePro\Model\Log\AbstractModel::PRIORITY_LOW
                    );

                    $otherListing->save();

                    continue;
                }

                $otherListing->setData('online_qty', 0);

                $tempLogMessages = array($this->helperFactory->getObject('Module\Translation')->__(
                    'Item qty was successfully changed from %from% to %to% .',
                    empty($currentOnlineQty) ? '"empty"' : $currentOnlineQty,
                    0
                ));

                if (!$otherListing->isStopped()) {
                    $statusChangedFrom = $this->helperFactory->getObject('Component\Amazon')
                        ->getHumanTitleByListingProductStatus($otherListing->getStatus());
                    $statusChangedTo = $this->helperFactory->getObject('Component\Amazon')
                        ->getHumanTitleByListingProductStatus(\Ess\M2ePro\Model\Listing\Product::STATUS_STOPPED);

                    if (!empty($statusChangedFrom) && !empty($statusChangedTo)) {
                        // M2ePro\TRANSLATIONS
                        // Item Status was successfully changed from "%from%" to "%to%" .
                        $tempLogMessages[] = $this->helperFactory->getObject('Module\Translation')->__(
                            'Item Status was successfully changed from "%from%" to "%to%" .',
                            $statusChangedFrom, $statusChangedTo
                        );
                    }

                    $otherListing->setData('status', \Ess\M2ePro\Model\Listing\Product::STATUS_STOPPED);
                }

                foreach ($tempLogMessages as $tempLogMessage) {
                    $logger->addProductMessage(
                        $otherListing->getId(),
                        \Ess\M2ePro\Helper\Data::INITIATOR_EXTENSION,
                        $logsActionId,
                        \Ess\M2ePro\Model\Listing\Other\Log::ACTION_CHANNEL_CHANGE,
                        $tempLogMessage,
                        \Ess\M2ePro\Model\Log\AbstractModel::TYPE_SUCCESS,
                        \Ess\M2ePro\Model\Log\AbstractModel::PRIORITY_LOW
                    );
                }

                $otherListing->save();
            }
        }
    }

    //########################################
}
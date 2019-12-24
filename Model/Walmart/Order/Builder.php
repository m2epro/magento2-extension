<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Order;

/**
 * Class \Ess\M2ePro\Model\Walmart\Order\Builder
 */
class Builder extends \Ess\M2ePro\Model\AbstractModel
{
    const INSTRUCTION_INITIATOR = 'order_builder';

    const STATUS_NOT_MODIFIED = 0;
    const STATUS_NEW = 1;
    const STATUS_UPDATED = 2;

    const UPDATE_STATUS = 'status';

    // M2ePro\TRANSLATIONS
    // Duplicated Walmart orders with ID #%id%.

    //########################################

    /** @var $helper \Ess\M2ePro\Model\Walmart\Order\Helper */
    private $helper = null;

    /** @var $order \Ess\M2ePro\Model\Account */
    private $account = null;

    /** @var $order \Ess\M2ePro\Model\Order */
    private $order = null;

    private $status = self::STATUS_NOT_MODIFIED;

    private $items = [];

    private $updates = [];

    protected $walmartFactory;
    protected $activeRecordFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $data = []
    ) {
        parent::__construct($helperFactory, $modelFactory, $data);

        $this->activeRecordFactory = $activeRecordFactory;
        $this->walmartFactory = $walmartFactory;

        $this->helper = $this->modelFactory->getObject('Walmart_Order_Helper');
    }

    //########################################

    public function initialize(\Ess\M2ePro\Model\Account $account, array $data = [])
    {
        $this->account = $account;

        $this->initializeData($data);
        $this->initializeOrder();
    }

    //########################################

    private function initializeData(array $data = [])
    {
        // Init general data
        // ---------------------------------------
        $this->setData('account_id', $this->account->getId());
        $this->setData('walmart_order_id', $data['walmart_order_id']);
        $this->setData('marketplace_id', $this->account->getChildObject()->getMarketplaceId());

        $itemsStatuses = [];
        foreach ($data['items'] as $item) {
            $itemsStatuses[$item['walmart_order_item_id']] = $item['status'];
        }
        $this->setData('status', $this->helper->getOrderStatus($itemsStatuses));

        $this->setData('purchase_update_date', $data['update_date']);
        $this->setData('purchase_create_date', $data['purchase_date']);
        // ---------------------------------------

        // Init sale data
        // ---------------------------------------
        $this->setData('paid_amount', (float)$data['amount_paid']);
        $this->setData('tax_details', $this->getHelper('Data')->jsonEncode($data['tax_details']));
        $this->setData('currency', $data['currency']);
        // ---------------------------------------

        // Init customer/shipping data
        // ---------------------------------------
        $this->setData('buyer_name', $data['buyer']['name']);
        $this->setData('buyer_email', $data['buyer']['email']);
        $this->setData('shipping_service', $data['shipping']['level']);
        $this->setData('shipping_address', $this->getHelper('Data')->jsonEncode($data['shipping']['address']));
        $this->setData('shipping_price', (float)$data['shipping']['price']);
        // ---------------------------------------

        $this->items = $data['items'];
    }

    //########################################

    private function initializeOrder()
    {
        $this->status = self::STATUS_NOT_MODIFIED;

        $existOrders = $this->walmartFactory->getObject('Order')->getCollection()
            ->addFieldToFilter('account_id', $this->account->getId())
            ->addFieldToFilter('walmart_order_id', $this->getData('walmart_order_id'))
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
            $this->order = $this->walmartFactory->getObject('Order');
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

    /**
     * @return \Ess\M2ePro\Model\Order
     */
    public function process()
    {
        $this->checkUpdates();

        $this->createOrUpdateOrder();
        $this->createOrUpdateItems();

        if ($this->isNew() && $this->getData('status') != \Ess\M2ePro\Model\Walmart\Order::STATUS_CANCELED) {
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

            /** @var $itemBuilder \Ess\M2ePro\Model\Walmart\Order\Item\Builder */
            $itemBuilder = $this->modelFactory->getObject('Walmart_Order_Item_Builder');
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
        if (!$this->isNew() && $this->getData('status') == \Ess\M2ePro\Model\Walmart\Order::STATUS_CANCELED) {
            $this->order->getChildObject()->setData('status', \Ess\M2ePro\Model\Walmart\Order::STATUS_CANCELED);
            $this->order->getChildObject()->setData('purchase_update_date', $this->getData('purchase_update_date'));
            $this->order->getChildObject()->save();
        } else {
            $this->order->addData($this->getData());
            $this->order->save();

            $this->order->getChildObject()->addData($this->getData());
            $this->order->getChildObject()->save();
        }

        $this->order->setAccount($this->account);
    }

    //########################################

    private function checkUpdates()
    {
        if ($this->hasUpdatedStatus()) {
            $this->updates[] = self::UPDATE_STATUS;
        }
    }

    private function hasUpdatedStatus()
    {
        if (!$this->isUpdated()) {
            return false;
        }

        return $this->getData('status') != $this->order->getChildObject()->getData('status');
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

        $magentoOrderUpdater->finishUpdate();
    }

    private function cancelMagentoOrder()
    {
        if (!$this->order->canCancelMagentoOrder()) {
            return;
        }

        $magentoOrderComments = [];
        $magentoOrderComments[] = '<b>Attention!</b> Order was canceled on Walmart.';

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

    private function processListingsProductsUpdates()
    {
        $logger = $this->activeRecordFactory->getObject('Listing\Log');
        $logger->setComponentMode(\Ess\M2ePro\Helper\Component\Walmart::NICK);

        $logsActionId = $this->activeRecordFactory->getObject('Listing\Log')->getResource()->getNextActionId();

        $parentsForProcessing = [];
        $listingsProductsIdsForNeedSynchRulesCheck = [];

        foreach ($this->items as $orderItem) {
            /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection $listingProductCollection */
            $listingProductCollection = $this->walmartFactory->getObject('Listing\Product')->getCollection();
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

                /** @var \Ess\M2ePro\Model\Walmart\Listing\Product $walmartListingProduct */
                $walmartListingProduct = $listingProduct->getChildObject();

                $currentOnlineQty = $walmartListingProduct->getOnlineQty();

                // if product was linked by sku during list action
                if ($listingProduct->isStopped() && $currentOnlineQty === null) {
                    continue;
                }

                $variationManager = $walmartListingProduct->getVariationManager();

                if ($variationManager->isRelationChildType()) {
                    $parentListingProduct = $variationManager->getTypeModel()->getParentListingProduct();
                    $parentsForProcessing[$parentListingProduct->getId()] = $parentListingProduct;
                }

                $this->activeRecordFactory->getObject('ProductChange')->addUpdateAction(
                    $listingProduct->getProductId(),
                    \Ess\M2ePro\Model\ProductChange::INITIATOR_SYNCHRONIZATION
                );

                if ($listingProduct->isSetProcessingLock('in_action')) {
                    $listingsProductsIdsForNeedSynchRulesCheck[] = $listingProduct->getId();
                }

                if ($currentOnlineQty > $orderItem['qty']) {
                    $walmartListingProduct->setData('online_qty', $currentOnlineQty - $orderItem['qty']);

                    // M2ePro\TRANSLATIONS
                    // Item QTY was successfully changed from %from% to %to% .
                    $tempLogMessage = $this->getHelper('Module\Translation')->__(
                        'Item QTY was successfully changed from %from% to %to% .',
                        $currentOnlineQty,
                        ($currentOnlineQty - $orderItem['qty'])
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

                    $walmartListingProduct->save();

                    continue;
                }

                $walmartListingProduct->setData('online_qty', 0);

                $tempLogMessages = [
                    $this->getHelper('Module\Translation')->__(
                        'Item QTY was successfully changed from %from% to %to% .',
                        $currentOnlineQty,
                        0
                    )
                ];

                if (!$listingProduct->isStopped()) {
                    $statusChangedFrom = $this->getHelper('Component\Walmart')
                        ->getHumanTitleByListingProductStatus($listingProduct->getStatus());
                    $statusChangedTo = $this->getHelper('Component\Walmart')
                        ->getHumanTitleByListingProductStatus(\Ess\M2ePro\Model\Listing\Product::STATUS_STOPPED);

                    if (!empty($statusChangedFrom) && !empty($statusChangedTo)) {
                        // M2ePro\TRANSLATIONS
                        // Item Status was successfully changed from "%from%" to "%to%" .
                        $tempLogMessages[] = $this->getHelper('Module\Translation')->__(
                            'Item Status was successfully changed from "%from%" to "%to%" .',
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
                        \Ess\M2ePro\Model\Log\AbstractModel::TYPE_SUCCESS,
                        \Ess\M2ePro\Model\Log\AbstractModel::PRIORITY_LOW
                    );
                }

                $walmartListingProduct->save();
                $listingProduct->save();
            }
        }

        if (!empty($parentsForProcessing)) {
            $massProcessor = $this->modelFactory->getObject(
                'Walmart_Listing_Product_Variation_Manager_Type_Relation_ParentRelation_Processor_Mass'
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
        $logger = $this->activeRecordFactory->getObject('Listing_Other_Log');
        $logger->setComponentMode(\Ess\M2ePro\Helper\Component\Walmart::NICK);

        $logsActionId = $this->activeRecordFactory->getObject('Listing_Other_Log')->getResource()->getNextActionId();

        foreach ($this->items as $orderItem) {
            /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection $listingOtherCollection */
            $listingOtherCollection = $this->walmartFactory->getObject('Listing\Other')->getCollection();
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

                /** @var \Ess\M2ePro\Model\Walmart\Listing\Other $walmartOtherListing */
                $walmartOtherListing = $otherListing->getChildObject();

                $currentOnlineQty = $walmartOtherListing->getData('online_qty');

                if ($currentOnlineQty > $orderItem['qty']) {
                    $walmartOtherListing->setData('online_qty', $currentOnlineQty - $orderItem['qty']);

                    // M2ePro\TRANSLATIONS
                    // Item QTY was successfully changed from %from% to %to% .
                    $tempLogMessage = $this->getHelper('Module\Translation')->__(
                        'Item QTY was successfully changed from %from% to %to% .',
                        $currentOnlineQty,
                        ($currentOnlineQty - $orderItem['qty'])
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

                    $walmartOtherListing->save();

                    continue;
                }

                $walmartOtherListing->setData('online_qty', 0);

                $tempLogMessages = [];

                if ($currentOnlineQty > 0) {
                    $tempLogMessages = [
                        $this->getHelper('Module\Translation')->__(
                            'Item qty was successfully changed from %from% to %to% .',
                            $currentOnlineQty,
                            0
                        )
                    ];
                }

                if (!$otherListing->isStopped()) {
                    $statusChangedFrom = $this->getHelper('Component\Walmart')
                        ->getHumanTitleByListingProductStatus($otherListing->getStatus());
                    $statusChangedTo = $this->getHelper('Component\Walmart')
                        ->getHumanTitleByListingProductStatus(\Ess\M2ePro\Model\Listing\Product::STATUS_STOPPED);

                    if (!empty($statusChangedFrom) && !empty($statusChangedTo)) {
                        // M2ePro\TRANSLATIONS
                        // Item Status was successfully changed from "%from%" to "%to%" .
                        $tempLogMessages[] = $this->getHelper('Module\Translation')->__(
                            'Item Status was successfully changed from "%from%" to "%to%" .',
                            $statusChangedFrom,
                            $statusChangedTo
                        );
                    }

                    $otherListing->setData(
                        'status_changer',
                        \Ess\M2ePro\Model\Listing\Product::STATUS_CHANGER_COMPONENT
                    );
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

                $walmartOtherListing->save();
                $otherListing->save();
            }
        }
    }

    //########################################
}

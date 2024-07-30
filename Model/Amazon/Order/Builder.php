<?php

namespace Ess\M2ePro\Model\Amazon\Order;

use Ess\M2ePro\Model\AbstractModel;

class Builder extends AbstractModel
{
    public const INSTRUCTION_INITIATOR = 'order_builder';

    public const STATUS_NOT_MODIFIED = 0;
    public const STATUS_NEW = 1;
    public const STATUS_UPDATED = 2;

    public const UPDATE_STATUS = 'status';
    public const UPDATE_EMAIL = 'email';
    public const UPDATE_B2B_VAT_REVERSE_CHARGE = 'b2b_vat_reverse_charge';
    public const UPDATE_REPLACEMENT_ORDER_ID = 'replacement_order_id';

    /** @var \Ess\M2ePro\Model\ActiveRecord\Factory  */
    protected $activeRecordFactory;
    /** @var \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory  */
    protected $amazonFactory;

    /** @var \Ess\M2ePro\Model\Account $order */
    protected $account = null;

    /** @var \Ess\M2ePro\Model\Order $order */
    protected $order = null;
    /** @var int  */
    protected $status = self::STATUS_NOT_MODIFIED;
    /** @var array  */
    protected $items = [];
    /** @var array  */
    protected $updates = [];
    /** @var \Ess\M2ePro\Model\Order\Note\Repository */
    private $noteRepository;
    /** @var \Ess\M2ePro\Model\Amazon\Order\UkTaxService */
    private $ukTaxService;

    public function __construct(
        \Ess\M2ePro\Model\Amazon\Order\UkTaxService $ukTaxService,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\Order\Note\Repository $noteRepository,
        array $data = []
    ) {
        parent::__construct($helperFactory, $modelFactory, $data);

        $this->ukTaxService = $ukTaxService;
        $this->amazonFactory = $amazonFactory;
        $this->activeRecordFactory = $activeRecordFactory;
        $this->noteRepository = $noteRepository;
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
        $this->setData('is_sold_by_amazon', $data['is_sold_by_amazon']);
        $this->setData('is_business', $data['is_business']);
        $this->setData('is_replacement', $data['is_replacement']);
        $this->setData('replaced_amazon_order_id', $data['replaced_amazon_order_id']);

        $this->setData('purchase_update_date', $data['purchase_update_date']);
        $this->setData('purchase_create_date', $data['purchase_create_date']);

        $this->setData('is_buyer_requested_cancel', $data['is_buyer_requested_cancel']);
        $this->setData('buyer_cancel_reason', $data['buyer_cancel_reason']);
        if (
            $data['is_buyer_requested_cancel'] &&
            $this->getData('status') == \Ess\M2ePro\Model\Amazon\Order::STATUS_UNSHIPPED
        ) {
            $this->setData('status', \Ess\M2ePro\Model\Amazon\Order::STATUS_CANCELLATION_REQUESTED);
        }

        // ---------------------------------------

        // Init sale data
        // ---------------------------------------
        $this->setData('paid_amount', (float)$data['paid_amount']);
        $this->setData('tax_details', \Ess\M2ePro\Helper\Json::encode($this->prepareTaxDetails($data)));
        $this->setData('ioss_number', $data['items'][0]['ioss_number']);
        $this->setData('tax_registration_id', $this->prepareTaxRegistrationId($data));
        $this->setData('discount_details', \Ess\M2ePro\Helper\Json::encode($data['discount_details']));
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
        $this->setData('shipping_category', $data['shipping_category']);
        $this->setData('shipping_mapping', $data['shipping_mapping']);
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

    protected function prepareTaxRegistrationId($data): ?string
    {
        $taxRegistrationKey = $this->getTaxRegistrationKey($data['marketplace_id']);

        if (empty($data[$taxRegistrationKey][0])) {
            return null;
        }

        $item = $data[$taxRegistrationKey][0];

        if (isset($item['value']) && is_string($item['value'])) {
            return $item['value'];
        }

        if (isset($item['tax_registration_id']) && is_string($item['tax_registration_id'])) {
            return $item['tax_registration_id'];
        }

        return null;
    }

    private function getTaxRegistrationKey(string $marketplaceId): string
    {
        if ($marketplaceId == \Ess\M2ePro\Helper\Component\Amazon::MARKETPLACE_BR) {
            return 'buyer_tax_info';
        }

        return 'tax_registration_details';
    }

    protected function isNeedSkipTax($data): bool
    {
        if ($this->isReplacement($data)) {
            return true;
        }

        if ($this->isSkipTaxForUS($data)) {
            return true;
        }

        if ($this->isSkipTaxForUkShipment($data)) {
            return true;
        }

        if ($this->isSkipTaxForEEAShipmentFromUkSite($data['shipping_address']['country_code'])) {
            return true;
        }

        return false;
    }

    /**
     * @param string $countryCode
     *
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    private function isSkipTaxForEEAShipmentFromUkSite(string $countryCode): bool
    {
        if (!$this->account->getChildObject()->isAmazonCollectsTaxForEEAShipmentFromUkSite()) {
            return false;
        }

        if (
            !in_array(
                strtoupper($countryCode),
                $this->account->getChildObject()->getExcludedCountries(),
                true
            )
        ) {
            return false;
        }

        return true;
    }

    protected function isSkipTaxForUkShipment(array $data)
    {
        $countryCode = strtoupper($data['shipping_address']['country_code']);
        if (!$this->ukTaxService->isSkipTaxForUkShipmentCountryCode($countryCode)) {
            return false;
        }

        if ($this->account->getChildObject()->isAmazonCollectsTaxForUKShipmentAvailable()) {
            return true;
        }

        try {
            if (
                $this->account->getChildObject()->isAmazonCollectsTaxForUKShipmentWithCertainPrice()
                && $this->isSumOfItemPriceLessThan135GBP(
                    $data['items']
                )
            ) {
                return true;
            }
        } catch (\Throwable $exception) {
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
            $result += $this->ukTaxService->convertPricetoGBP($item['price'], trim($item['currency']));
        }

        return $result;
    }

    protected function isSumOfItemPriceLessThan135GBP($items): bool
    {
        return $this->ukTaxService->isSumOfItemPriceLessThan135GBP(
            $this->calculateItemPrice($items)
        );
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

        if (
            $this->isNew() && !$this->getData('is_afn_channel') &&
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

            /** @var \Ess\M2ePro\Model\Amazon\Order\Item\Builder $itemBuilder */
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
                \Ess\M2ePro\Helper\Json::encode($this->getData('shipping_address'))
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

        $replacedAmazonOrderId = $this->getData('replaced_amazon_order_id');
        if (
            $this->getData('is_replacement') &&
            $replacedAmazonOrderId &&
            ($this->isNew() || $this->hasUpdate(self::UPDATE_REPLACEMENT_ORDER_ID)) &&
            ($originalOrder = $this->findOriginalOrder($replacedAmazonOrderId))
        ) {
            $this->createReplacementOrderNote($replacedAmazonOrderId);
            $this->createOriginalOrderLog($originalOrder);
        }

        if (
            $this->getData('status') == \Ess\M2ePro\Model\Amazon\Order::STATUS_CANCELLATION_REQUESTED &&
            $this->hasUpdate(self::UPDATE_STATUS)
        ) {
            if ($reason = $this->getData('buyer_cancel_reason')) {
                $noteText = 'A buyer requested order cancellation. Reason: ' .
                    $this->getHelper('Data')->escapeHtml($reason);
            } else {
                $noteText = 'A buyer requested order cancellation. The reason was not specified.';
            }

            $this->noteRepository->create($this->order->getId(), $noteText);
        }

        if ($this->isNew()) {
            $this->createNoteBuyerCustomizedItem($this->items);
        }

        $this->order->setAccount($this->account);

        if ($this->order->getChildObject()->isCanceled() && $this->order->getReserve()->isPlaced()) {
            $this->order->getReserve()->cancel();
        }
    }

    private function createNoteBuyerCustomizedItem(array $orderItemsData): void
    {
        foreach ($orderItemsData as $item) {
            if (empty($item['buyer_customized_info'])) {
                continue;
            }
            $note = (string)__(
                "Customization for SKU %sku: <a href='%customized_link'>Link</a><br>",
                [
                    'sku' => $item['sku'],
                    'customized_link' => $item['buyer_customized_info'],
                ]
            );

            $this->noteRepository->create($this->order->getId(), $note);
        }
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Order|null
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    private function findOriginalOrder(string $replacedAmazonOrderId): ?\Ess\M2ePro\Model\Order
    {
        $existOrders = $this->amazonFactory->getObject('Order')->getCollection()
                                           ->addFieldToFilter('account_id', $this->account->getId())
                                           ->addFieldToFilter('amazon_order_id', $replacedAmazonOrderId)
                                           ->setOrder('id', \Magento\Framework\Data\Collection::SORT_ORDER_ASC)
                                           ->getItems();

        if (!empty($existOrders)) {
            return reset($existOrders);
        }

        return null;
    }

    private function createReplacementOrderNote(string $replacedAmazonOrderId)
    {
        $noteText = (string) __('Original order ID %1', $replacedAmazonOrderId);
        $this->noteRepository->create($this->order->getData('id'), $noteText);
    }

    private function createOriginalOrderLog(\Ess\M2ePro\Model\Order $originalOrder): void
    {
        $message = (string)__(
            "Replacement order ID %1 was requested",
            $this->getData('amazon_order_id')
        );
        $originalOrder->addInfoLog(
            $message,
            [],
            [],
            false
        );
    }

    private function hasReplacementOrderIdUpdate(): bool
    {
        if (!$this->isUpdated()) {
            return false;
        }

        return $this->order->getChildObject()
                           ->getReplacedAmazonOrderId() !== $this->getData('replaced_amazon_order_id');
    }

    protected function checkUpdates(): void
    {
        if ($this->hasUpdatedStatus()) {
            $this->updates[] = self::UPDATE_STATUS;
        }

        if ($this->hasUpdatedEmail()) {
            $this->updates[] = self::UPDATE_EMAIL;
        }

        if ($this->hasUpdatedVat()) {
            $this->updates[] = self::UPDATE_B2B_VAT_REVERSE_CHARGE;
        }

        if ($this->hasReplacementOrderIdUpdate()) {
            $this->updates[] = self::UPDATE_REPLACEMENT_ORDER_ID;
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

    protected function hasUpdatedVat(): bool
    {
        if (!$this->isUpdated()) {
            return false;
        }

        /** @var \Ess\M2ePro\Model\Amazon\Order $amazonOrder */
        $amazonOrder = $this->order->getChildObject();
        if (!$amazonOrder->isBusiness()) {
            return false;
        }

        if ($this->getData('status') === \Ess\M2ePro\Model\Amazon\Order::STATUS_CANCELED) {
            return false;
        }

        $oldTaxDetails = \Ess\M2ePro\Helper\Json::decode($amazonOrder->getData('tax_details'));
        $oldTaxSum = (float)array_sum(array_values($oldTaxDetails));

        $newTaxDetails = \Ess\M2ePro\Helper\Json::decode($this->getData('tax_details'));
        $newTaxSum = (float)array_sum(array_values($newTaxDetails));

        return $newTaxSum !== $oldTaxSum;
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

        /** @var \Ess\M2ePro\Model\Magento\Order\Updater $magentoOrderUpdater */
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

        if ($this->hasUpdate(self::UPDATE_B2B_VAT_REVERSE_CHARGE)) {
            $this->order->markAsVatChanged();
            $message = 'Reverse charge (0% VAT) applied on Amazon';
            $magentoOrderUpdater->updateComments([__($message)]);
            $this->order->addInfoLog(
                $message,
                [],
                [],
                false,
                [\Ess\M2ePro\Model\Order::ADDITIONAL_DATA_KEY_VAT_REVERSE_CHARGE => true]
            );
        }

        $magentoOrderUpdater->finishUpdate();
    }

    protected function cancelMagentoOrder()
    {
        $magentoOrderComments = [];
        $magentoOrderComments[] = '<b>Attention!</b> Order was canceled on Amazon.';
        $result = $this->order->canCancelMagentoOrder();

        if (is_bool($result)) {
            if ($result === true) {
                try {
                    $this->order->cancelMagentoOrder();
                } catch (\Exception $e) {
                    $this->getHelper('Module_Exception')->process($e);
                }

                $this->addCommentsToMagentoOrder($this->order, $magentoOrderComments);
            }

            return;
        }

        $magentoOrderComments[] = 'Order cannot be canceled in Magento. Reason: ' . $result;
        $this->addCommentsToMagentoOrder($this->order, $magentoOrderComments);
    }

    private function addCommentsToMagentoOrder(\Ess\M2ePro\Model\Order $order, $comments)
    {
        /** @var \Ess\M2ePro\Model\Magento\Order\Updater $magentoOrderUpdater */
        $magentoOrderUpdater = $this->modelFactory->getObject('Magento_Order_Updater');
        $magentoOrderUpdater->setMagentoOrder($order->getMagentoOrder());
        $magentoOrderUpdater->updateComments($comments);
        $magentoOrderUpdater->finishUpdate();
    }

    //########################################

    protected function processListingsProductsUpdates()
    {
        /** @var \Ess\M2ePro\Model\Listing\Log $logger */
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
                if (!$listingProduct->isListed() && !$listingProduct->isInactive()) {
                    continue;
                }

                /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
                $amazonListingProduct = $listingProduct->getChildObject();

                if ($amazonListingProduct->isAfnChannel()) {
                    continue;
                }

                $currentOnlineQty = $amazonListingProduct->getOnlineQty();

                // if product was linked by sku during list action
                if ($listingProduct->isInactive() && $currentOnlineQty === null) {
                    continue;
                }

                $variationManager = $amazonListingProduct->getVariationManager();

                if ($variationManager->isRelationChildType()) {
                    $parentListingProduct = $variationManager->getTypeModel()->getParentListingProduct();
                    $parentsForProcessing[$parentListingProduct->getId()] = $parentListingProduct;
                }

                /** @var \Ess\M2ePro\Model\Listing\Product\Instruction $instruction */
                $instruction = $this->activeRecordFactory->getObject('Listing_Product_Instruction');
                $instruction->setData(
                    [
                        'listing_product_id' => $listingProduct->getId(),
                        'component' => \Ess\M2ePro\Helper\Component\Amazon::NICK,
                        'type' => \Ess\M2ePro\Model\Amazon\Listing\Product::INSTRUCTION_TYPE_CHANNEL_QTY_CHANGED,
                        'initiator' => self::INSTRUCTION_INITIATOR,
                        'priority' => 80,
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

                $tempLogMessages = [];
                if ($currentOnlineQty !== 0) {
                    $tempLogMessages[] = (string)__(
                        'Item QTY was changed from %from to 0.',
                        ['from' => $currentOnlineQty]
                    );
                }

                if (!$listingProduct->isInactive()) {
                    $statusChangedFrom = $this->getHelper('Component\Amazon')
                                              ->getHumanTitleByListingProductStatus($listingProduct->getStatus());
                    $statusChangedTo = $this->getHelper('Component\Amazon')
                                            ->getHumanTitleByListingProductStatus(
                                                \Ess\M2ePro\Model\Listing\Product::STATUS_INACTIVE
                                            );

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
                    $listingProduct->setData('status', \Ess\M2ePro\Model\Listing\Product::STATUS_INACTIVE);
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
                if (!$otherListing->isListed() && !$otherListing->isInactive()) {
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

                if (!$otherListing->isInactive()) {
                    $otherListing->setData(
                        'status_changer',
                        \Ess\M2ePro\Model\Listing\Product::STATUS_CHANGER_COMPONENT
                    );
                    $otherListing->setData('status', \Ess\M2ePro\Model\Listing\Product::STATUS_INACTIVE);
                }

                $otherListing->save();
            }
        }
    }

    private function isReplacement($data): bool
    {
        return (bool)$data['is_replacement'];
    }
}

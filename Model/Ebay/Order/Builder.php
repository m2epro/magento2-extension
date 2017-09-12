<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Order;

use Ess\M2ePro\Model\AbstractModel;

class Builder extends AbstractModel
{
    const STATUS_NOT_MODIFIED = 0;
    const STATUS_NEW          = 1;
    const STATUS_UPDATED      = 2;

    const UPDATE_COMPLETED_CHECKOUT = 'completed_checkout';
    const UPDATE_COMPLETED_PAYMENT  = 'completed_payment';
    const UPDATE_COMPLETED_SHIPPING = 'completed_shipping';
    const UPDATE_BUYER_MESSAGE      = 'buyer_message';
    const UPDATE_PAYMENT_DATA       = 'payment_data';
    const UPDATE_SHIPPING_TAX_DATA  = 'shipping_tax_data';
    const UPDATE_EMAIL              = 'email';

    //########################################

    // M2ePro\TRANSLATIONS
    // Payment status was updated to Paid on eBay.
    // Shipping status was updated to Shipped on eBay.
    // Buyer has changed the shipping address of this order at the time of completing payment on eBay.
    // Duplicated eBay Orders with ID #%id%.
    // Order Creation Rules were not met. Press Create Order Button at Order View Page to create it anyway.
    // Magento Order #%order_id% should be canceled as new combined eBay Order #%new_id% was created.
    // eBay Order #%old_id% was deleted as new combined Order #%new_id% was created.

    //########################################

    private $moduleConfig;

    private $activeRecordFactory;

    private $ebayFactory;

    private $helper;

    /** @var $order \Ess\M2ePro\Model\Account */
    private $account = NULL;

    /** @var $order \Ess\M2ePro\Model\Order */
    private $order = NULL;

    private $items = array();

    private $externalTransactions = array();

    private $status = self::STATUS_NOT_MODIFIED;

    private $updates = array();

    private $relatedOrders = array();

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\Config\Manager\Module $moduleConfig,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\Ebay\Order\Helper $orderHelper,
        array $data = []
    )
    {
        $this->moduleConfig = $moduleConfig;
        $this->activeRecordFactory = $activeRecordFactory;
        $this->ebayFactory = $ebayFactory;
        $this->helper = $orderHelper;
        parent::__construct($helperFactory, $modelFactory, $data);
    }

    //########################################

    public function initialize(\Ess\M2ePro\Model\Account $account, array $data = array())
    {
        $this->account = $account;

        $this->initializeData($data);
        $this->initializeMarketplace();
        $this->initializeOrder();
    }

    //########################################

    protected function initializeData(array $data = array())
    {
        // ---------------------------------------
        $this->setData('account_id', $this->account->getId());

        $this->setData('ebay_order_id', $data['identifiers']['ebay_order_id']);
        $this->setData('selling_manager_id', $data['identifiers']['selling_manager_id']);

        $this->setData('order_status', $this->helper->getOrderStatus($data['statuses']['order']));
        $this->setData('checkout_status', $this->helper->getCheckoutStatus($data['statuses']['checkout']));

        $this->setData('purchase_update_date', $data['purchase_update_date']);
        $this->setData('purchase_create_date', $data['purchase_create_date']);
        // ---------------------------------------

        // ---------------------------------------
        $this->setData('paid_amount', (float)$data['selling']['paid_amount']);
        $this->setData('saved_amount', (float)$data['selling']['saved_amount']);
        $this->setData('currency', $data['selling']['currency']);

        if (empty($data['selling']['tax_details']) || !is_array($data['selling']['tax_details'])) {
            $this->setData('tax_details', null);
        } else {
            $this->setData('tax_details', $data['selling']['tax_details']);
        }
        // ---------------------------------------

        // ---------------------------------------
        $this->setData('buyer_user_id', trim($data['buyer']['user_id']));
        $this->setData('buyer_name', trim($data['buyer']['name']));
        $this->setData('buyer_email', trim($data['buyer']['email']));
        $this->setData('buyer_message', $data['buyer']['message']);
        $this->setData('buyer_tax_id', trim($data['buyer']['tax_id']));
        // ---------------------------------------

        // ---------------------------------------
        $this->externalTransactions = $data['payment']['external_transactions'];
        unset($data['payment']['external_transactions']);

        $this->setData('payment_details', $data['payment']);

        $paymentStatus = $this->helper->getPaymentStatus(
            $data['payment']['method'], $data['payment']['date'], $data['payment']['status']
        );
        $this->setData('payment_status', $paymentStatus);
        // ---------------------------------------

        // ---------------------------------------
        $this->setData('shipping_details', $data['shipping']);

        $shippingStatus = $this->helper->getShippingStatus(
            $data['shipping']['date'], !empty($data['shipping']['service'])
        );
        $this->setData('shipping_status', $shippingStatus);
        // ---------------------------------------

        // ---------------------------------------
        $this->items = $data['items'];
        // ---------------------------------------
    }

    //########################################

    private function initializeMarketplace()
    {
        // Get first order item
        $item = reset($this->items);

        if (empty($item['site'])) {
            return;
        }

        $shippingDetails = $this->getData('shipping_details');
        $paymentDetails = $this->getData('payment_details');

        $marketplace = $this->ebayFactory->getCachedObjectLoaded('Marketplace',$item['site'], 'code');

        $shippingDetails['service'] = $this->helper->getShippingServiceNameByCode(
            $shippingDetails['service'], $marketplace->getId()
        );
        $paymentDetails['method'] = $this->helper->getPaymentMethodNameByCode(
            $paymentDetails['method'], $marketplace->getId()
        );

        $this->setData('marketplace_id', $marketplace->getId());
        $this->setData('shipping_details', $shippingDetails);
        $this->setData('payment_details', $paymentDetails);
    }

    //########################################

    private function initializeOrder()
    {
        $this->status = self::STATUS_NOT_MODIFIED;

        $existOrders = $this->ebayFactory->getObject('Order')->getCollection()
            ->addFieldToFilter('account_id', $this->account->getId())
            ->addFieldToFilter('ebay_order_id', $this->getData('ebay_order_id'))
            ->setOrder('id', \Magento\Framework\Data\Collection::SORT_ORDER_DESC)
            ->getItems();
        $existOrdersNumber = count($existOrders);

        // New order
        // ---------------------------------------
        if ($existOrdersNumber == 0) {
            $this->status = self::STATUS_NEW;
            $this->order = $this->ebayFactory->getObject('Order');
            $this->order->setStatusUpdateRequired(true);

            if ($this->isCombined()) {
                $this->relatedOrders = $this->activeRecordFactory
                                            ->getObject('Ebay\Order')
                                            ->getResource()
                                            ->getOrdersContainingItemsFromOrder(
                                                  $this->account->getId(),
                                                  $this->items
                                              );
            }

            return;
        }
        // ---------------------------------------

        // duplicated M2ePro orders. remove M2E Pro order without magento order id or newest order
        // ---------------------------------------
        if ($existOrdersNumber > 1) {

            $isDeleted = false;

            foreach ($existOrders as $key => $order) {
                /** @var \Ess\M2ePro\Model\Order $order */

                $magentoOrderId = $order->getData('magento_order_id');
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

    public function process()
    {
        if (!$this->canCreateOrUpdateOrder()) {
            return NULL;
        }

        $this->checkUpdates();

        $this->createOrUpdateOrder();
        $this->createOrUpdateItems();
        $this->createOrUpdateExternalTransactions();

        $finalFee = $this->order->getChildObject()->getFinalFee();
        $magentoOrder = $this->order->getMagentoOrder();

        if (!empty($finalFee) && !empty($magentoOrder)) {
            $paymentAdditionalData = $magentoOrder->getPayment()->getAdditionalData();
            $paymentAdditionalData = @unserialize($paymentAdditionalData);
            if (!empty($paymentAdditionalData)) {
                $paymentAdditionalData['channel_final_fee'] = $finalFee;
                $magentoOrder->getPayment()->setAdditionalData(serialize($paymentAdditionalData));
                $magentoOrder->getPayment()->save();
            }
        }

        if ($this->isNew()) {
            $this->processNew();
        }

        if ($this->isUpdated()) {
            $this->processOrderUpdates();
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

            /** @var $itemBuilder \Ess\M2ePro\Model\Ebay\Order\Item\Builder */
            $itemBuilder = $this->modelFactory->getObject('Ebay\Order\Item\Builder');
            $itemBuilder->initialize($itemData);

            $item = $itemBuilder->process();
            $item->setOrder($this->order);

            $itemsCollection->removeItemByKey($item->getId());
            $itemsCollection->addItem($item);
        }
    }

    //########################################

    private function createOrUpdateExternalTransactions()
    {
        $externalTransactionsCollection = $this->order->getChildObject()->getExternalTransactionsCollection();
        $externalTransactionsCollection->load();

        foreach ($this->externalTransactions as $transactionData) {
            $transactionData['order_id'] = $this->order->getId();

            /** @var $transactionBuilder \Ess\M2ePro\Model\Ebay\Order\ExternalTransaction\Builder */
            $transactionBuilder = $this->modelFactory->getObject('Ebay\Order\ExternalTransaction\Builder');
            $transactionBuilder->initialize($transactionData);

            $transaction = $transactionBuilder->process();
            $transaction->setOrder($this->order);

            $externalTransactionsCollection->removeItemByKey($transaction->getId());
            $externalTransactionsCollection->addItem($transaction);
        }
    }

    //########################################

    public function isRefund()
    {
        $paymentDetails = $this->getData('payment_details');
        return $paymentDetails['is_refund'];
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function isSingle()
    {
        return count($this->items) == 1;
    }

    /**
     * @return bool
     */
    public function isCombined()
    {
        return count($this->items) > 1;
    }

    // ---------------------------------------

    private function hasExternalTransactions()
    {
        return count($this->externalTransactions) > 0;
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function isNew()
    {
        return $this->status == self::STATUS_NEW;
    }

    /**
     * @return bool
     */
    public function isUpdated()
    {
        return $this->status == self::STATUS_UPDATED;
    }

    //########################################

    private function canCreateOrUpdateOrder()
    {
        if ($this->isNew() && $this->isRefund()) {
            return false;
        }

        if (empty($this->relatedOrders)) {
            return true;
        }

        if ($this->getData('checkout_status') == \Ess\M2ePro\Model\Ebay\Order::CHECKOUT_STATUS_COMPLETED) {
            return true;
        }

        if ($this->getData('order_status') == \Ess\M2ePro\Model\Ebay\Order::ORDER_STATUS_CANCELLED ||
            $this->getData('order_status') == \Ess\M2ePro\Model\Ebay\Order::ORDER_STATUS_INACTIVE
        ) {
            return false;
        }

        if (count($this->relatedOrders) == 1) {
            /** @var \Ess\M2ePro\Model\Order $relatedOrder */
            $relatedOrder = reset($this->relatedOrders);

            if ($relatedOrder->getItemsCollection()->getSize() == count($this->items)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return \Ess\M2ePro\Model\Order
     */
    private function createOrUpdateOrder()
    {
        $this->prepareShippingAddress();

        $this->setData('tax_details', $this->getHelper('Data')->jsonEncode($this->getData('tax_details')));
        $this->setData('shipping_details', $this->getHelper('Data')->jsonEncode($this->getData('shipping_details')));
        $this->setData('payment_details', $this->getHelper('Data')->jsonEncode($this->getData('payment_details')));

        $this->order->addData($this->getData());
        $this->order->save();

        $this->order->getChildObject()->addData($this->getData());
        $this->order->getChildObject()->save();

        $this->order->setAccount($this->account);

        if ($this->getData('order_status') == \Ess\M2ePro\Model\Ebay\Order::ORDER_STATUS_CANCELLED &&
            $this->order->getReserve()->isPlaced()
        ) {
            $this->order->getReserve()->cancel();
        }
    }

    private function prepareShippingAddress()
    {
        $shippingDetails = $this->getData('shipping_details');
        $shippingAddress = $shippingDetails['address'];

        $shippingAddress['company'] = '';

        if (!isset($shippingAddress['street']) || !is_array($shippingAddress['street'])) {
            $shippingAddress['street'] = array();
        }

        $shippingAddress['street'] = array_filter($shippingAddress['street']);

        $group = '/ebay/order/settings/marketplace_'.(int)$this->getData('marketplace_id').'/';
        $useFirstStreetLineAsCompany = $this->moduleConfig->getGroupValue($group, 'use_first_street_line_as_company');

        if ($useFirstStreetLineAsCompany && count($shippingAddress['street']) > 1) {
            $shippingAddress['company'] = array_shift($shippingAddress['street']);
        }

        $shippingDetails['address'] = $shippingAddress;
        $this->setData('shipping_details', $shippingDetails);
    }

    //########################################

    private function processNew()
    {
        if (!$this->isNew()) {
            return;
        }

        if ($this->isCombined()) {
            $this->processOrdersContainingItemsFromCurrentOrder();
        }

        /** @var $ebayAccount \Ess\M2ePro\Model\Ebay\Account */
        $ebayAccount = $this->account->getChildObject();

        if ($this->order->hasListingItems() && !$ebayAccount->isMagentoOrdersListingsModeEnabled()) {
            return;
        }

        if ($this->order->hasOtherListingItems() && !$ebayAccount->isMagentoOrdersListingsOtherModeEnabled()) {
            return;
        }

        if (!$this->order->getChildObject()->canCreateMagentoOrder()) {
            $this->order->addWarningLog('Magento Order was not created. Reason: %msg%', array(
                'msg' => 'Order Creation Rules were not met. ' .
                         'Press Create Order Button at Order View Page to create it anyway.'
            ));
            return;
        }
    }

    private function processOrdersContainingItemsFromCurrentOrder()
    {
        /** @var $log \Ess\M2ePro\Model\Order\Log */
        $log = $this->activeRecordFactory->getObject('Order\Log');
        $log->setComponentMode(\Ess\M2ePro\Helper\Component\Ebay::NICK);

        /** @var \Ess\M2ePro\Model\Order $order */
        foreach ($this->relatedOrders as $order) {
            if ($order->canCancelMagentoOrder()) {
                $description = 'Magento Order #%order_id% should be canceled '.
                               'as new combined eBay order #%new_id% was created.';
                $description = $this->getHelper('Module\Log')->encodeDescription(
                    $description, array(
                    '!order_id' => $order->getMagentoOrder()->getRealOrderId(),
                    '!new_id' => $this->order->getChildObject()->getEbayOrderId()
                ));

                $log->addMessage($order->getId(), $description, \Ess\M2ePro\Model\Log\AbstractModel::TYPE_WARNING);

                try {
                    $order->cancelMagentoOrder();
                } catch (\Exception $e) {}
            }

            if ($order->getReserve()->isPlaced()) {
                $order->getReserve()->release();
            }

            $description = 'eBay Order #%old_id% was deleted as new combined eBay order #%new_id% was created.';
            $description = $this->getHelper('Module\Log')->encodeDescription(
                $description, array(
                '!old_id' => $order->getChildObject()->getEbayOrderId(),
                '!new_id' => $this->order->getChildObject()->getEbayOrderId()
            ));

            $log->addMessage($order->getId(), $description, \Ess\M2ePro\Model\Log\AbstractModel::TYPE_WARNING);

            $order->delete();
        }
    }

    //########################################

    private function checkUpdates()
    {
        if (!$this->isUpdated()) {
            return;
        }

        if ($this->hasUpdatedCompletedCheckout()) {
            $this->updates[] = self::UPDATE_COMPLETED_CHECKOUT;
        }
        if ($this->hasUpdatedBuyerMessage()) {
            $this->updates[] = self::UPDATE_BUYER_MESSAGE;
        }
        if ($this->hasUpdatedCompletedPayment()) {
            $this->updates[] = self::UPDATE_COMPLETED_PAYMENT;
        }
        if ($this->hasUpdatedPaymentData()) {
            $this->updates[] = self::UPDATE_PAYMENT_DATA;
        }
        if ($this->hasUpdatedShippingTaxData()) {
            $this->updates[] = self::UPDATE_SHIPPING_TAX_DATA;
        }
        if ($this->hasUpdatedCompletedShipping()) {
            $this->updates[] = self::UPDATE_COMPLETED_SHIPPING;
        }
        if ($this->hasUpdatedEmail()) {
            $this->updates[] = self::UPDATE_EMAIL;
        }
    }

    // ---------------------------------------

    private function hasUpdatedCompletedCheckout()
    {
        if (!$this->isUpdated() || $this->order->getChildObject()->isCheckoutCompleted()) {
            return false;
        }

        return $this->getData('checkout_status') == \Ess\M2ePro\Model\Ebay\Order::CHECKOUT_STATUS_COMPLETED;
    }

    private function hasUpdatedBuyerMessage()
    {
        if (!$this->isUpdated()) {
            return false;
        }

        if ($this->getData('buyer_message') == '') {
            return false;
        }

        return $this->getData('buyer_message') != $this->order->getChildObject()->getBuyerMessage();
    }

    // ---------------------------------------

    private function hasUpdatedCompletedPayment()
    {
        if (!$this->isUpdated() || $this->order->getChildObject()->isPaymentCompleted()) {
            return false;
        }

        return $this->getData('payment_status') == \Ess\M2ePro\Model\Ebay\Order::PAYMENT_STATUS_COMPLETED;
    }

    // ---------------------------------------

    private function hasUpdatedCompletedShipping()
    {
        if (!$this->isUpdated() || $this->order->getChildObject()->isShippingCompleted()) {
            return false;
        }

        return $this->getData('shipping_status') == \Ess\M2ePro\Model\Ebay\Order::SHIPPING_STATUS_COMPLETED;
    }

    // ---------------------------------------

    private function hasUpdatedPaymentData()
    {
        if (!$this->isUpdated()) {
            return false;
        }

        /** @var $ebayOrder \Ess\M2ePro\Model\Ebay\Order */
        $ebayOrder = $this->order->getChildObject();
        $paymentDetails = $this->getData('payment_details');

        if ($ebayOrder->getPaymentMethod() != $paymentDetails['method']) {
            return true;
        }

        if (!$ebayOrder->hasExternalTransactions() && $this->hasExternalTransactions()) {
            return true;
        }

        return false;
    }

    // ---------------------------------------

    private function hasUpdatedShippingTaxData()
    {
        if (!$this->isUpdated()) {
            return false;
        }

        /** @var $ebayOrder \Ess\M2ePro\Model\Ebay\Order */
        $ebayOrder = $this->order->getChildObject();
        $shippingDetails = $this->getData('shipping_details');
        $taxDetails      = $this->getData('tax_details');

        if (!empty($shippingDetails['price']) && $shippingDetails['price'] != $ebayOrder->getShippingPrice() ||
            !empty($shippingDetails['service']) && $shippingDetails['service'] != $ebayOrder->getShippingService())
        {
            return true;
        }

        if ((!empty($taxDetails['rate']) && $taxDetails['rate'] != $ebayOrder->getTaxRate()) ||
            (!empty($taxDetails['amount']) && $taxDetails['amount'] != $ebayOrder->getTaxAmount()))
        {
            return true;
        }

        return false;
    }

    // ---------------------------------------

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

    private function processOrderUpdates()
    {
        if (!$this->hasUpdates()) {
            return;
        }

        if ($this->hasUpdate(self::UPDATE_COMPLETED_CHECKOUT)) {
            $this->order->addSuccessLog('Buyer has completed checkout on eBay.');
            $this->order->setStatusUpdateRequired(true);
        }

        if ($this->hasUpdate(self::UPDATE_COMPLETED_PAYMENT)) {
            $this->order->addSuccessLog('Payment status was updated to Paid on eBay.');
            $this->order->setStatusUpdateRequired(true);
        }

        if ($this->hasUpdate(self::UPDATE_COMPLETED_SHIPPING)) {
            $this->order->addSuccessLog('Shipping status was updated to Shipped on eBay.');
            $this->order->setStatusUpdateRequired(true);
        }

        if ($this->hasUpdate(self::UPDATE_SHIPPING_TAX_DATA) && $this->order->getMagentoOrderId()) {

            $message  = 'Attention! Shipping/Tax details have been modified on the channel.';
            $message .= 'Magento order is already created and cannot be updated to reflect these changes.';
            $this->order->addWarningLog($message);
        }
    }

    private function processMagentoOrderUpdates()
    {
        if (!$this->hasUpdates()) {
            return;
        }

        $magentoOrder = $this->order->getMagentoOrder();
        if (is_null($magentoOrder)) {
            return;
        }

        /** @var $magentoOrderUpdater \Ess\M2ePro\Model\Magento\Order\Updater */
        $magentoOrderUpdater = $this->modelFactory->getObject('Magento\Order\Updater');
        $magentoOrderUpdater->setMagentoOrder($magentoOrder);

        $proxy = $this->order->getProxy();
        $proxy->setStore($this->order->getStore());

        if ($this->hasUpdate(self::UPDATE_PAYMENT_DATA)) {
            $magentoOrderUpdater->updatePaymentData($proxy->getPaymentData());
        }

        if ($this->hasUpdate(self::UPDATE_COMPLETED_CHECKOUT)) {
            $magentoOrderUpdater->updateShippingAddress($proxy->getAddressData());
            $magentoOrderUpdater->updateCustomerAddress($proxy->getAddressData());
        }

        if ($this->hasUpdate(self::UPDATE_BUYER_MESSAGE)) {
            $magentoOrderUpdater->updateComments($proxy->getChannelComments());
        }

        if ($this->hasUpdate(self::UPDATE_EMAIL)) {
            $magentoOrderUpdater->updateCustomerEmail($this->order->getChildObject()->getBuyerEmail());
        }

        $magentoOrderUpdater->finishUpdate();
    }

    //########################################
}
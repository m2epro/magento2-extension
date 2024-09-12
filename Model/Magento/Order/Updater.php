<?php

namespace Ess\M2ePro\Model\Magento\Order;

class Updater
{
    private \Magento\Customer\Model\CustomerFactory $customerFactory;
    private \Magento\Customer\Model\AddressFactory $customerAddressFactory;
    private \Magento\Sales\Model\Order\AddressFactory $addressFactory;
    private \Magento\Sales\Model\Order $magentoOrder;
    private \Ess\M2ePro\Helper\Data $dataHelper;

    private bool $needSave = false;

    public function __construct(
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Customer\Model\AddressFactory $customerAddressFactory,
        \Magento\Sales\Model\Order\AddressFactory $addressFactory,
        \Ess\M2ePro\Helper\Data $dataHelper
    ) {
        $this->customerFactory = $customerFactory;
        $this->customerAddressFactory = $customerAddressFactory;
        $this->addressFactory = $addressFactory;
        $this->dataHelper = $dataHelper;
    }

    /**
     * Set magento order for updating
     */
    public function setMagentoOrder(\Magento\Sales\Model\Order $order): self
    {
        $this->magentoOrder = $order;

        return $this;
    }

    private function getMagentoCustomer(): ?\Magento\Customer\Model\Customer
    {
        if ($this->magentoOrder->getCustomerIsGuest()) {
            return null;
        }

        $customer = $this->magentoOrder->getCustomer();
        if ($customer instanceof \Magento\Framework\DataObject && $customer->getId()) {
            return $customer;
        }

        $customer = $this->customerFactory->create()->load($this->magentoOrder->getCustomerId());
        if ($customer->getId()) {
            $this->magentoOrder->setCustomer($customer);
        }

        return $customer->getId() ? $customer : null;
    }

    public function updateShippingAddress(array $addressInfo): void
    {
        if ($this->magentoOrder->isCanceled()) {
            return;
        }

        $shippingAddress = $this->magentoOrder->getShippingAddress();
        if ($shippingAddress instanceof \Magento\Sales\Model\Order\Address) {
            $shippingAddress->addData($addressInfo);
        } else {
            /** @var \Magento\Sales\Model\Order\Address $shippingAddress */
            $shippingAddress = $this->addressFactory->create();
            $shippingAddress->setCustomerId($this->magentoOrder->getCustomerId());
            $shippingAddress->addData($addressInfo);

            // we need to set shipping address to order before address save to init parent_id field
            $this->magentoOrder->setShippingAddress($shippingAddress);
        }
        $shippingAddress->save();

        // we need to save order to update data in table sales_flat_order_grid
        // setData method will force magento model to save entity
        $this->magentoOrder->setForceUpdateGridRecords(false);
        $this->needSave = true;
    }

    public function updateBillingAddress(array $addressInfo): void
    {
        if ($this->magentoOrder->isCanceled()) {
            return;
        }

        $billingAddress = $this->magentoOrder->getBillingAddress();
        if ($billingAddress instanceof \Magento\Sales\Model\Order\Address) {
            $billingAddress->addData($addressInfo);
        } else {
            /** @var \Magento\Sales\Model\Order\Address $billingAddress */
            $billingAddress = $this->addressFactory->create();
            $billingAddress->setCustomerId($this->magentoOrder->getCustomerId());
            $billingAddress->addData($addressInfo);

            $this->magentoOrder->setBillingAddress($billingAddress);
        }
        $billingAddress->save();

        $this->magentoOrder->setForceUpdateGridRecords(false);
        $this->needSave = true;
    }

    public function updateShippingDescription($shippingDescription): void
    {
        $this->magentoOrder->setData('shipping_description', $shippingDescription);
        $this->needSave = true;
    }

    /**
     * Update customer email
     */
    public function updateCustomerEmail($email): void
    {
        if ($this->magentoOrder->isCanceled()) {
            return;
        }

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return;
        }

        if ($this->magentoOrder->getCustomerEmail() != $email) {
            $this->magentoOrder->setCustomerEmail($email);
            $this->needSave = true;
        }

        if (!$this->magentoOrder->getCustomerIsGuest()) {
            $customer = $this->getMagentoCustomer();

            if ($customer === null) {
                return;
            }

            if (strpos($customer->getEmail(), \Ess\M2ePro\Model\Magento\Customer::FAKE_EMAIL_POSTFIX) === false) {
                return;
            }

            $customer->setEmail($email)->save();
        }
    }

    /**
     * Update customer address
     */
    public function updateCustomerAddress(array $customerAddress): void
    {
        if ($this->magentoOrder->isCanceled()) {
            return;
        }

        if ($this->magentoOrder->getCustomerIsGuest()) {
            return;
        }

        $customer = $this->getMagentoCustomer();

        if ($customer === null) {
            return;
        }

        /** @var \Magento\Customer\Model\Address $customerAddress */
        $customerAddress = $this->customerAddressFactory->create()
                                                        ->setData($customerAddress)
                                                        ->setCustomerId($customer->getId())
                                                        ->setIsDefaultBilling(false)
                                                        ->setIsDefaultShipping(false);

        $customerAddress->save();
    }

    /**
     * Update payment data (payment method, transactions, etc)
     */
    public function updatePaymentData(array $newPaymentData): void
    {
        if ($this->magentoOrder->isCanceled()) {
            return;
        }

        $payment = $this->magentoOrder->getPayment();
        if ($payment instanceof \Magento\Sales\Model\Order\Payment) {
            $payment->setAdditionalData(
                $this->dataHelper->serialize($newPaymentData)
            )->save();
        }
    }

    /**
     * Add notes
     */
    public function updateComments($comments): void
    {
        if ($this->magentoOrder->isCanceled()) {
            return;
        }

        if (empty($comments)) {
            return;
        }

        !is_array($comments) && $comments = [$comments];

        $header = '<br/><b><u>' . __('M2E Pro Notes') . ':</u></b><br/><br/>';
        $comments = implode('<br/><br/>', $comments);

        $this->magentoOrder->addCommentToStatusHistory($header . $comments);
        $this->needSave = true;
    }

    /**
     * Update status
     */
    public function updateStatus($status): void
    {
        if ($this->magentoOrder->isCanceled()) {
            return;
        }

        if ($status == '') {
            return;
        }

        if (
            $this->magentoOrder->getState() != \Magento\Sales\Model\Order::STATE_COMPLETE
            && $this->magentoOrder->getState() != \Magento\Sales\Model\Order::STATE_CLOSED
        ) {
            $this->magentoOrder->setState(\Magento\Sales\Model\Order::STATE_PROCESSING);
        }

        $this->magentoOrder->setStatus($status);

        $this->needSave = true;
    }

    public function cancel(): void
    {
        $this->magentoOrder->setActionFlag(\Magento\Sales\Model\Order::ACTION_FLAG_CANCEL, true);
        $this->magentoOrder->setActionFlag(\Magento\Sales\Model\Order::ACTION_FLAG_UNHOLD, true);

        $this->magentoOrder->cancel()->save();
    }

    /**
     * Save magento order only once and only if it's needed
     */
    public function finishUpdate(): void
    {
        if ($this->needSave) {
            $this->magentoOrder->save();
        }
    }
}

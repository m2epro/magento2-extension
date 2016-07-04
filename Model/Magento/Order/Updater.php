<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Magento\Order;

class Updater extends \Ess\M2ePro\Model\AbstractModel
{
    // M2ePro\TRANSLATIONS
    // Cancel is not allowed for Orders which were already Canceled.
    // Cancel is not allowed for Orders with Invoiced Items.
    // Cancel is not allowed for Orders which were put on Hold.
    // Cancel is not allowed for Orders which were Completed or Closed.

    //########################################

    protected $customerFactory;
    protected $customerAddressFactory;
    protected $addressFactory;

    /** @var $magentoOrder \Magento\Sales\Model\Order */
    protected $magentoOrder;

    protected $needSave = false;

    //########################################

    public function __construct(
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Customer\Model\AddressFactory $customerAddressFactory,
        \Magento\Sales\Model\Order\AddressFactory $addressFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    )
    {
        $this->customerFactory = $customerFactory;
        $this->customerAddressFactory = $customerAddressFactory;
        $this->addressFactory = $addressFactory;
        parent::__construct($helperFactory, $modelFactory);
    }

    //########################################

    /**
     * Set magento order for updating
     *
     * @param \Magento\Sales\Model\Order $order
     * @return $this
     */
    public function setMagentoOrder(\Magento\Sales\Model\Order $order)
    {
        $this->magentoOrder = $order;
        return $this;
    }

    //########################################

    /**
     * @return \Magento\Customer\Model\Customer
     */
    private function getMagentoCustomer()
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

    //########################################

    /**
     * Update shipping address
     *
     * @param array $addressInfo
     */
    public function updateShippingAddress(array $addressInfo)
    {
        if ($this->magentoOrder->isCanceled()) {
            return;
        }

        $shippingAddress = $this->magentoOrder->getShippingAddress();
        if ($shippingAddress instanceof \Magento\Sales\Model\Order\Address) {
            $shippingAddress->addData($addressInfo);
            $shippingAddress->implodeStreetAddress()->save();
        } else {
            /** @var $shippingAddress \Magento\Sales\Model\Order\Address */
            $shippingAddress = $this->addressFactory->create();
            $shippingAddress->setCustomerId($this->magentoOrder->getCustomerId());
            $shippingAddress->addData($addressInfo);
            $shippingAddress->implodeStreetAddress();

            // we need to set shipping address to order before address save to init parent_id field
            $this->magentoOrder->setShippingAddress($shippingAddress);
            $shippingAddress->save();
        }

        // we need to save order to update data in table sales_flat_order_grid
        // setData method will force magento model to save entity
        $this->magentoOrder->setForceUpdateGridRecords(false);
        $this->needSave = true;
    }

    public function updateShippingDescription($shippingDescription)
    {
        $this->magentoOrder->setData('shipping_description', $shippingDescription);
        $this->needSave = true;
    }

    //########################################

    /**
     * Update customer email
     *
     * @param $email
     * @return null
     */
    public function updateCustomerEmail($email)
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

            if (is_null($customer)) {
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
     *
     * @param array $customerAddress
     */
    public function updateCustomerAddress(array $customerAddress)
    {
        if ($this->magentoOrder->isCanceled()) {
            return;
        }

        if ($this->magentoOrder->getCustomerIsGuest()) {
            return;
        }

        $customer = $this->getMagentoCustomer();

        if (is_null($customer)) {
            return;
        }

        /** @var $customerAddress \Magento\Customer\Model\Address */
        $customerAddress = $this->customerAddressFactory->create()
            ->setData($customerAddress)
            ->setCustomerId($customer->getId())
            ->setIsDefaultBilling(false)
            ->setIsDefaultShipping(false);
        $customerAddress->implodeStreetAddress();
        $customerAddress->save();
    }

    //########################################

    /**
     * Update payment data (payment method, transactions, etc)
     *
     * @param array $newPaymentData
     */
    public function updatePaymentData(array $newPaymentData)
    {
        if ($this->magentoOrder->isCanceled()) {
            return;
        }

        $payment = $this->magentoOrder->getPayment();

        if ($payment instanceof \Magento\Sales\Model\Order\Payment) {
            $payment->setAdditionalData(serialize($newPaymentData))->save();
        }
    }

    //########################################

    /**
     * Add notes
     *
     * @param mixed $comments
     * @return null
     */
    public function updateComments($comments)
    {
        if ($this->magentoOrder->isCanceled()) {
            return;
        }

        if (empty($comments)) {
            return;
        }

        !is_array($comments) && $comments = array($comments);

        $header = '<br/><b><u>' . $this->getHelper('Module\Translation')->__('M2E Pro Notes') . ':</u></b><br/><br/>';
        $comments = implode('<br/><br/>', $comments);

        $this->magentoOrder->addStatusHistoryComment($header . $comments);
        $this->needSave = true;
    }

    //########################################

    /**
     * Update status
     *
     * @param $status
     * @return null
     */
    public function updateStatus($status)
    {
        if ($this->magentoOrder->isCanceled()) {
            return;
        }

        if ($status == '') {
            return;
        }

        if ($this->magentoOrder->getState() == \Magento\Sales\Model\Order::STATE_COMPLETE
            || $this->magentoOrder->getState() == \Magento\Sales\Model\Order::STATE_CLOSED
        ) {
            $this->magentoOrder->setStatus($status);
        } else {
            $this->magentoOrder->setState(\Magento\Sales\Model\Order::STATE_PROCESSING, $status);
        }

        $this->needSave = true;
    }

    //########################################

    public function cancel()
    {
        $this->magentoOrder->setActionFlag(\Magento\Sales\Model\Order::ACTION_FLAG_CANCEL, true);
        $this->magentoOrder->setActionFlag(\Magento\Sales\Model\Order::ACTION_FLAG_UNHOLD, true);

        if ($this->magentoOrder->isCanceled()) {
            //throw new \Ess\M2ePro\Model\Exception('Cancel is not allowed for Orders which were already Canceled.');
            return;
        }

        if ($this->magentoOrder->canUnhold()) {
            throw new \Ess\M2ePro\Model\Exception('Cancel is not allowed for Orders which were put on Hold.');
        }

        if ($this->magentoOrder->getState() === \Magento\Sales\Model\Order::STATE_COMPLETE ||
            $this->magentoOrder->getState() === \Magento\Sales\Model\Order::STATE_CLOSED) {
            throw new \Ess\M2ePro\Model\Exception('Cancel is not allowed for Orders which were Completed or Closed.');
        }

        $allInvoiced = true;
        foreach ($this->magentoOrder->getAllItems() as $item) {
            if ($item->getQtyToInvoice()) {
                $allInvoiced = false;
                break;
            }
        }
        if ($allInvoiced) {
            throw new \Ess\M2ePro\Model\Exception('Cancel is not allowed for Orders with Invoiced Items.');
        }

        $this->magentoOrder->cancel()->save();
    }

    //########################################

    /**
     * Save magento order only once and only if it's needed
     */
    public function finishUpdate()
    {
        if ($this->needSave) {
            $this->magentoOrder->save();
        }
    }

    //########################################
}
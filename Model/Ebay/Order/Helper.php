<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Order;

class Helper extends \Ess\M2ePro\Model\AbstractModel
{
    const EBAY_ORDER_STATUS_ACTIVE    = 'Active';
    const EBAY_ORDER_STATUS_COMPLETED = 'Completed';
    const EBAY_ORDER_STATUS_CANCELLED = 'Cancelled';
    const EBAY_ORDER_STATUS_INACTIVE  = 'Inactive';

    const EBAY_CHECKOUT_STATUS_COMPLETE = 'Complete';

    const EBAY_PAYMENT_METHOD_NONE      = 'None';
    const EBAY_PAYMENT_STATUS_SUCCEEDED = 'NoPaymentFailure';

    private $resourceConnection;

    //########################################

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    )
    {
        $this->resourceConnection = $resourceConnection;
        parent::__construct($helperFactory, $modelFactory);
    }

    //########################################

    public function getOrderStatus($orderStatusEbay)
    {
        $orderStatus = null;

        switch ($orderStatusEbay) {
            case self::EBAY_ORDER_STATUS_ACTIVE:
                $orderStatus = \Ess\M2ePro\Model\Ebay\Order::ORDER_STATUS_ACTIVE;
                break;

            case self::EBAY_ORDER_STATUS_COMPLETED:
                $orderStatus = \Ess\M2ePro\Model\Ebay\Order::ORDER_STATUS_COMPLETED;
                break;

            case self::EBAY_ORDER_STATUS_CANCELLED:
                $orderStatus = \Ess\M2ePro\Model\Ebay\Order::ORDER_STATUS_CANCELLED;
                break;

            case self::EBAY_ORDER_STATUS_INACTIVE:
                $orderStatus = \Ess\M2ePro\Model\Ebay\Order::ORDER_STATUS_INACTIVE;
                break;
        }

        return $orderStatus;
    }

    //########################################

    public function getCheckoutStatus($checkoutStatusEbay)
    {
        if ($checkoutStatusEbay == self::EBAY_CHECKOUT_STATUS_COMPLETE) {
            return \Ess\M2ePro\Model\Ebay\Order::CHECKOUT_STATUS_COMPLETED;
        }

        return \Ess\M2ePro\Model\Ebay\Order::CHECKOUT_STATUS_INCOMPLETE;
    }

    public function getPaymentStatus($paymentMethod, $paymentDate, $paymentStatusEbay)
    {
        if ($paymentMethod == self::EBAY_PAYMENT_METHOD_NONE) {

            if ($paymentDate) {
                return \Ess\M2ePro\Model\Ebay\Order::PAYMENT_STATUS_COMPLETED;
            }

            if ($paymentStatusEbay == self::EBAY_PAYMENT_STATUS_SUCCEEDED) {
                return \Ess\M2ePro\Model\Ebay\Order::PAYMENT_STATUS_NOT_SELECTED;
            }

        } else {

            if ($paymentStatusEbay == self::EBAY_PAYMENT_STATUS_SUCCEEDED) {
                return $paymentDate
                    ?\Ess\M2ePro\Model\Ebay\Order::PAYMENT_STATUS_COMPLETED
                    :\Ess\M2ePro\Model\Ebay\Order::PAYMENT_STATUS_PROCESS;
            }
        }

        return \Ess\M2ePro\Model\Ebay\Order::PAYMENT_STATUS_ERROR;
    }

    public function getShippingStatus($shippingDate, $isShippingServiceSelected)
    {
        if ($shippingDate == '') {
            return $isShippingServiceSelected
                ?\Ess\M2ePro\Model\Ebay\Order::SHIPPING_STATUS_PROCESSING
                :\Ess\M2ePro\Model\Ebay\Order::SHIPPING_STATUS_NOT_SELECTED;
        }

        return \Ess\M2ePro\Model\Ebay\Order::SHIPPING_STATUS_COMPLETED;
    }

    //########################################

    public function getPaymentMethodNameByCode($code, $marketplaceId)
    {
        if ((int)$marketplaceId <= 0) {
            return $code;
        }

        $connRead = $this->resourceConnection->getConnection();
        $tableDictMarketplace = $this->resourceConnection->getTableName('m2epro_ebay_dictionary_marketplace');

        $dbSelect = $connRead->select()
            ->from($tableDictMarketplace, 'payments')
            ->where('`marketplace_id` = ?', (int)$marketplaceId);
        $marketplace = $connRead->fetchRow($dbSelect);

        if (!$marketplace) {
            return $code;
        }

        $payments = (array)$this->getHelper('Data')->jsonDecode($marketplace['payments']);

        foreach ($payments as $payment) {
            if ($payment['ebay_id'] == $code) {
                return $payment['title'];
            }
        }

        return $code;
    }

    public function getShippingServiceNameByCode($code, $marketplaceId)
    {
        if ((int)$marketplaceId <= 0) {
            return $code;
        }

        $connRead          = $this->resourceConnection->getConnection();
        $tableDictShipping = $this->resourceConnection->getTableName('m2epro_ebay_dictionary_shipping');

        $dbSelect = $connRead->select()
            ->from($tableDictShipping, 'title')
            ->where('`marketplace_id` = ?', (int)$marketplaceId)
            ->where('`ebay_id` = ?', $code);
        $shipping = $connRead->fetchRow($dbSelect);

        return !empty($shipping['title']) ? $shipping['title'] : $code;
    }

    //########################################
}
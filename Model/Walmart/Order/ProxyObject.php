<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Order;

/**
 * Class \Ess\M2ePro\Model\Walmart\Order\ProxyObject
 */
class ProxyObject extends \Ess\M2ePro\Model\Order\ProxyObject
{
    /** @var \Ess\M2ePro\Model\Walmart\Order\Item\ProxyObject[] */
    protected $removedProxyItems = [];

    //########################################

    /**
     * @return mixed
     */
    public function getChannelOrderNumber()
    {
        return $this->order->getWalmartOrderId();
    }

    //########################################

    /**
     * @return array
     */
    public function getPaymentData()
    {
        $paymentData = [
            'method'                => $this->payment->getCode(),
            'component_mode'        => \Ess\M2ePro\Helper\Component\Walmart::NICK,
            'payment_method'        => '',
            'channel_order_id'      => $this->order->getWalmartOrderId(),
            'channel_final_fee'     => 0,
            'cash_on_delivery_cost' => 0,
            'transactions'          => []
        ];

        return $paymentData;
    }

    //########################################

    /**
     * @return array
     */
    public function getShippingData()
    {
        $additionalData = '';

        $shippingDateTo = $this->order->getShippingDateTo();
        if (!empty($shippingDateTo)) {
            $shippingDate = $this->getHelper('Data')->gmtDateToTimezone(
                $shippingDateTo,
                false,
                'M d, Y, H:i:s'
            );
            $additionalData .= "Ship By Date: {$shippingDate} | ";
        }

        if (!empty($additionalData)) {
            $additionalData = ' | ' . $additionalData;
        }

        return [
            'carrier_title'   => $this->getHelper('Module\Translation')->__('Walmart Shipping'),
            'shipping_method' => $this->order->getShippingService() . $additionalData,
            'shipping_price'  => $this->getBaseShippingPrice(),
        ];
    }

    /**
     * @return float
     */
    protected function getShippingPrice()
    {
        $price = $this->order->getShippingPrice();

        if ($this->isTaxModeNone() && $this->getShippingPriceTaxRate() > 0) {
            $price += $this->order->getShippingPriceTaxAmount();
        }

        return $price;
    }

    //########################################

    /**
     * @return array
     */
    public function getChannelComments()
    {
        $comments = [];

        // Removed Order Items
        // ---------------------------------------
        if (!empty($this->removedProxyItems)) {
            $comment = '<u>' .
                $this->getHelper('Module\Translation')->__(
                    'The following SKUs have zero price and can not be included in Magento order line items'
                ) .
                ':</u><br/>';

            $zeroItems = [];
            foreach ($this->removedProxyItems as $item) {
                $productSku = $item->getMagentoProduct()->getSku();
                $qtyPurchased = $item->getQty();

                $zeroItems[] = "<b>{$productSku}</b>: {$qtyPurchased} QTY";
            }

            $comments[] = $comment . implode('<br/>,', $zeroItems);
        }
        // ---------------------------------------

        return $comments;
    }

    //########################################

    /**
     * @return bool
     */
    public function hasTax()
    {
        return $this->order->getProductPriceTaxRate() > 0;
    }

    /**
     * @return bool
     */
    public function isSalesTax()
    {
        return $this->hasTax();
    }

    /**
     * @return bool
     */
    public function isVatTax()
    {
        return false;
    }

    // ---------------------------------------

    /**
     * @return float|int
     */
    public function getProductPriceTaxRate()
    {
        return $this->order->getProductPriceTaxRate();
    }

    /**
     * @return float|int
     */
    public function getShippingPriceTaxRate()
    {
        return $this->order->getShippingPriceTaxRate();
    }

    //########################################
}

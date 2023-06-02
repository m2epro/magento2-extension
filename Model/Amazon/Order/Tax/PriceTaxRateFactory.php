<?php

namespace Ess\M2ePro\Model\Amazon\Order\Tax;

use Magento\Framework\ObjectManagerInterface;

class PriceTaxRateFactory
{
    /** @var ObjectManagerInterface */
    private $objectManager;

    public function __construct(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function createProductPriceTaxRateByOrder(\Ess\M2ePro\Model\Amazon\Order $order): ProductPriceTaxRate
    {
        return $this->objectManager->create(
            ProductPriceTaxRate::class,
            [
                'taxAmount' => $order->getProductPriceTaxAmount(),
                'giftTaxAmount' => $order->getGiftPriceTaxAmount(),
                'subTotalPrice' => $order->getSubtotalPrice(),
                'promotionDiscountAmount' => $order->getPromotionDiscountAmount(),
                'isEnabledRoundingOfValue' => $order->getAmazonAccount()->isEnabledRoundingOfTaxRateValue(),
            ]
        );
    }

    public function createShippingPriceTaxRateByOrder(\Ess\M2ePro\Model\Amazon\Order $order): ShippingPriceTaxRate
    {
        return $this->objectManager->create(
            ShippingPriceTaxRate::class,
            [
                'taxAmount' => $order->getShippingPriceTaxAmount(),
                'shippingPrice' => $order->getShippingPrice(),
                'shippingDiscountAmount' => $order->getShippingDiscountAmount(),
                'isEnabledRoundingOfValue' => $order->getAmazonAccount()->isEnabledRoundingOfTaxRateValue(),
            ]
        );
    }
}

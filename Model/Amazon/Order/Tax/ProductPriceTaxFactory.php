<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Order\Tax;

use Magento\Framework\ObjectManagerInterface;

class ProductPriceTaxFactory
{
    /** @var ObjectManagerInterface */
    private $objectManager;

    public function __construct(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * @param ProductPriceTax\Parameters $parameters
     *
     * @return ProductPriceTax
     */
    public function create(ProductPriceTax\Parameters $parameters): ProductPriceTax
    {
        return $this->objectManager->create(
            ProductPriceTax::class,
            ['parameters' => $parameters]
        );
    }

    public function createByOrder(\Ess\M2ePro\Model\Amazon\Order $order): ProductPriceTax
    {
        $params = $this->createParameters($order);
        return $this->create($params);
    }

    public function createParameters(\Ess\M2ePro\Model\Amazon\Order $order): ProductPriceTax\Parameters
    {
        return $this->objectManager->create(
            ProductPriceTax\Parameters::class,
            [
                'taxAmount' => $order->getProductPriceTaxAmount(),
                'giftTaxAmount' => $order->getGiftPriceTaxAmount(),
                'subTotalPrice' => $order->getSubtotalPrice(),
                'promotionDiscountAmount' => $order->getPromotionDiscountAmount(),
                'isEnabledRoundingOfTaxRateValue' => $order->getAmazonAccount()->isEnabledRoundingOfTaxRateValue()
            ]
        );
    }
}

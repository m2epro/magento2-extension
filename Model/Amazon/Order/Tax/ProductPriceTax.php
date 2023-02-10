<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Order\Tax;

class ProductPriceTax implements \Ess\M2ePro\Model\Order\Tax\ProductPriceTaxInterface
{
    /** @var ProductPriceTax\Parameters  */
    private $parameters;

    public function __construct(ProductPriceTax\Parameters $parameters)
    {
        $this->parameters = $parameters;
    }

    /**
     * @return float|int
     */
    public function getTaxRateValue()
    {
        $rate = $this->getCalculatedTaxRate();

        if ($rate === 0) {
            return $rate;
        }

        return $this->isEnabledRoundingOfTaxRateValue()
            ? (int)round($rate)
            : round($rate, 4);
    }

    /**
     * @return float|int
     */
    public function getNotRoundedTaxRateValue()
    {
        $rate = $this->getCalculatedTaxRate();

        return $rate === 0 ? $rate : round($rate, 4);
    }

    /**
     * @return float|int
     */
    private function getCalculatedTaxRate()
    {
        $taxAmount = $this->parameters->getTaxAmount() + $this->parameters->getGiftTaxAmount();
        if ($taxAmount <= 0) {
            return 0;
        }

        $diff = $this->parameters->getSubtotalPrice()  - $this->parameters->getPromotionDiscountAmount();

        if ($diff <= 0) {
            return 0;
        }

        return ($taxAmount / $diff) * 100;
    }

    /**
     * @return bool
     */
    public function isEnabledRoundingOfTaxRateValue(): bool
    {
        return $this->parameters->isEnabledRoundingOfTaxRateValue();
    }
}

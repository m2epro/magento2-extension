<?php

namespace Ess\M2ePro\Model\Amazon\Order\Tax;

class ProductPriceTaxRate implements \Ess\M2ePro\Model\Order\Tax\PriceTaxRateInterface
{
    /** @var float */
    private $taxAmount;
    /** @var float */
    private $giftTaxAmount;
    /** @var float */
    private $subTotalPrice;
    /** @var float */
    private $promotionDiscountAmount;
    /** @var bool */
    private $isEnabledRoundingOfValue;

    public function __construct(
        float $taxAmount,
        float $giftTaxAmount,
        float $subTotalPrice,
        float $promotionDiscountAmount,
        bool $isEnabledRoundingOfValue
    ) {
        $this->taxAmount = $taxAmount;
        $this->giftTaxAmount = $giftTaxAmount;
        $this->subTotalPrice = $subTotalPrice;
        $this->promotionDiscountAmount = $promotionDiscountAmount;
        $this->isEnabledRoundingOfValue = $isEnabledRoundingOfValue;
    }

    /**
     * @return float|int
     */
    public function getValue()
    {
        $rate = $this->getCalculatedValue();

        if ($rate === 0) {
            return $rate;
        }

        return $this->isEnabledRoundingOfValue
            ? (int)round($rate)
            : round($rate, 4);
    }

    /**
     * @return float|int
     */
    public function getNotRoundedValue()
    {
        $rate = $this->getCalculatedValue();

        return $rate === 0 ? $rate : round($rate, 4);
    }

    /**
     * @return float|int
     */
    private function getCalculatedValue()
    {
        $taxAmount = $this->taxAmount + $this->giftTaxAmount;
        if ($taxAmount <= 0) {
            return 0;
        }

        $diff = $this->subTotalPrice - $this->promotionDiscountAmount;

        if ($diff <= 0) {
            return 0;
        }

        return ($taxAmount / $diff) * 100;
    }

    public function isEnabledRoundingOfValue(): bool
    {
        return $this->isEnabledRoundingOfValue;
    }
}

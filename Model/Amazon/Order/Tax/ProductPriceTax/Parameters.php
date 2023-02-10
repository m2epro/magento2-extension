<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Order\Tax\ProductPriceTax;

class Parameters
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
    private $isEnabledRoundingOfTaxRateValue;

    public function __construct(
        float $taxAmount,
        float $giftTaxAmount,
        float $subTotalPrice,
        float $promotionDiscountAmount,
        bool $isEnabledRoundingOfTaxRateValue
    ) {
        $this->taxAmount = $taxAmount;
        $this->giftTaxAmount = $giftTaxAmount;
        $this->subTotalPrice = $subTotalPrice;
        $this->promotionDiscountAmount = $promotionDiscountAmount;
        $this->isEnabledRoundingOfTaxRateValue = $isEnabledRoundingOfTaxRateValue;
    }

    /**
     * @return float
     */
    public function getTaxAmount(): float
    {
        return $this->taxAmount;
    }

    /**
     * @return float
     */
    public function getGiftTaxAmount(): float
    {
        return $this->giftTaxAmount;
    }

    /**
     * @return float
     */
    public function getSubTotalPrice(): float
    {
        return $this->subTotalPrice;
    }

    /**
     * @return float
     */
    public function getPromotionDiscountAmount(): float
    {
        return $this->promotionDiscountAmount;
    }

    /**
     * @return bool
     */
    public function isEnabledRoundingOfTaxRateValue(): bool
    {
        return $this->isEnabledRoundingOfTaxRateValue;
    }
}

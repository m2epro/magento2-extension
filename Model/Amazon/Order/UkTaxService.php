<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Amazon\Order;

class UkTaxService
{
    /** @var \Ess\M2ePro\Model\Currency */
    private $currency;

    public function __construct(
        \Ess\M2ePro\Model\Currency $currency
    ) {
        $this->currency = $currency;
    }

    public function isSkipTaxForUkShipmentCountryCode(string $countryCode): bool
    {
        return in_array($countryCode, ['GB', 'UK']);
    }

    public function isSumOfItemPriceLessThan135GBP(float $itemsPrice): bool
    {
        return $itemsPrice < 135;
    }

    public function convertPriceToGBP(float $price, string $currency): float
    {
        return $this->currency->convertPriceToCurrency($price, $currency, 'GBP');
    }
}

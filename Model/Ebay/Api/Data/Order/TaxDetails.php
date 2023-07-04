<?php

namespace Ess\M2ePro\Model\Ebay\Api\Data\Order;

use Ess\M2ePro\Api\Ebay\Data\Order\TaxDetailsInterface;

class TaxDetails extends \Ess\M2ePro\Api\DataObject implements TaxDetailsInterface
{
    public function getRate(): float
    {
        return (float)$this->getData(self::RATE_KEY);
    }

    public function getAmount(): float
    {
        return (float)$this->getData(self::AMOUNT_KEY);
    }

    public function getIsVat(): bool
    {
        return (bool)$this->getData(self::IS_VAT_KEY);
    }

    public function getIncludesShipping(): bool
    {
        return (bool)$this->getData(self::INCLUDES_SHIPPING_KEY);
    }

    public function getShippingRate(): float
    {
        return (float)$this->getData(self::SHIPPING_RATE_KEY);
    }
}

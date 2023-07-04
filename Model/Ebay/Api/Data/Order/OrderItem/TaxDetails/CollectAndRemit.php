<?php

namespace Ess\M2ePro\Model\Ebay\Api\Data\Order\OrderItem\TaxDetails;

class CollectAndRemit extends \Ess\M2ePro\Api\DataObject implements
    \Ess\M2ePro\Api\Ebay\Data\Order\OrderItem\TaxDetails\CollectAndRemitInterface
{
    public function getTaxAmount(): float
    {
        return (float)$this->getData(self::TAX_AMOUNT_KEY);
    }

    public function getSubtotalAmount(): float
    {
        return (float)$this->getData(self::SUBTOTAL_AMOUNT_KEY);
    }

    public function getWasteRecyclingFee(): float
    {
        return (float)$this->getData(self::WASTE_RECYCLING_FEE_KEY);
    }

    public function getTaxExcluded(): bool
    {
        return (bool)$this->getData(self::TAX_EXCLUDED_KEY);
    }
}

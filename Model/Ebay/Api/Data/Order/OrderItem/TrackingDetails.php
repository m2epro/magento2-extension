<?php

namespace Ess\M2ePro\Model\Ebay\Api\Data\Order\OrderItem;

class TrackingDetails extends \Ess\M2ePro\Api\DataObject implements
    \Ess\M2ePro\Api\Ebay\Data\Order\OrderItem\TrackingDetailsInterface
{
    public function setNumber(string $number): void
    {
        $this->setData(self::NUMBER_KEY, $number);
    }

    public function getNumber(): ?string
    {
        return $this->getData(self::NUMBER_KEY);
    }

    public function setTitle(string $title): void
    {
        $this->setData(self::TITLE_KEY, $title);
    }

    public function getTitle(): ?string
    {
        return $this->getData(self::TITLE_KEY);
    }
}

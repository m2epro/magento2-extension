<?php

namespace Ess\M2ePro\Model\Ebay\Api\Data\Order;

class Buyer extends \Ess\M2ePro\Api\DataObject implements \Ess\M2ePro\Api\Ebay\Data\Order\BuyerInterface
{
    public function getName(): ?string
    {
        return $this->getData(self::NAME_KEY);
    }

    public function getEmail(): ?string
    {
        return $this->getData(self::EMAIL_KEY);
    }

    public function getUserId(): ?string
    {
        return $this->getData(self::USER_ID_KEY);
    }

    public function getMessage(): ?string
    {
        return $this->getData(self::MESSAGE_KEY);
    }

    public function getTaxId(): ?string
    {
        return $this->getData(self::TAX_ID_KEY);
    }
}

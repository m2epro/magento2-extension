<?php

namespace Ess\M2ePro\Model\Ebay\Api\Data\Order;

class PaymentDetails extends \Ess\M2ePro\Api\DataObject implements
    \Ess\M2ePro\Api\Ebay\Data\Order\PaymentDetailsInterface
{
    public function getDate(): ?string
    {
        return $this->getData(self::DATE_KEY);
    }

    public function getMethod(): ?string
    {
        return $this->getData(self::METHOD_KEY);
    }

    public function getStatus(): ?string
    {
        return $this->getData(self::STATUS_KEY);
    }

    public function getIsRefund(): bool
    {
        return (bool)$this->getData(self::IS_REFUND_KEY);
    }
}

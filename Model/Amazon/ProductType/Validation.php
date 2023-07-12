<?php

namespace Ess\M2ePro\Model\Amazon\ProductType;

class Validation extends \Ess\M2ePro\Model\ActiveRecord\AbstractModel
{
    public const STATUS_INVALID = 0;
    public const STATUS_VALID = 1;

    public function _construct()
    {
        parent::_construct();
        $this->_init(\Ess\M2ePro\Model\ResourceModel\Amazon\ProductType\Validation::class);
    }

    public function getStatus(): int
    {
        return (int)$this->getData('status');
    }

    public function setValidStatus(): void
    {
        $this->setData('status', self::STATUS_VALID);
    }

    public function setInvalidStatus(): void
    {
        $this->setData('status', self::STATUS_INVALID);
    }

    public function getListingProductId(): int
    {
        return (int)$this->getData('listing_product_id');
    }

    public function setListingProductId(int $listingProductId): void
    {
        $this->setData('listing_product_id', $listingProductId);
    }

    public function getErrorMessages(): array
    {
        return $this->getSettings('error_messages');
    }

    public function setErrorMessages(array $errorMessages): void
    {
        $this->setSettings('error_messages', $errorMessages);
    }

    public function addErrorMessage(string $errorMessage): void
    {
        $errorMessages = $this->getErrorMessages();
        $errorMessages[] = $errorMessage;

        $this->setErrorMessages($errorMessages);
    }

    public function isValid(): bool
    {
        return $this->getStatus() === self::STATUS_VALID;
    }

    public function touchUpdateDate(): void
    {
        $date = \Ess\M2ePro\Helper\Date::createCurrentGmt()->format('Y-m-d H:i:s');
        $this->setData('update_date', $date);
    }

    public function touchCreateDate(): void
    {
        $date = \Ess\M2ePro\Helper\Date::createCurrentGmt()->format('Y-m-d H:i:s');
        $this->setData('create_date', $date);
    }
}

<?php

namespace Ess\M2ePro\Model\Ebay\Api\Data\Order\ShippingDetails;

use Ess\M2ePro\Api\Ebay\Data\Order\ShippingDetails\AddressInterface;

class Address extends \Ess\M2ePro\Api\DataObject implements AddressInterface
{
    public function getCountryCode(): ?string
    {
        return $this->getData(self::COUNTRY_CODE_KEY);
    }

    public function getCountryName(): ?string
    {
        return $this->getData(self::COUNTRY_NAME_KEY);
    }

    public function getCity(): ?string
    {
        return $this->getData(self::CITY_KEY);
    }

    public function getState(): ?string
    {
        return $this->getData(self::STATE_KEY);
    }

    public function getPostalCode(): ?string
    {
        return $this->getData(self::POSTAL_CODE_KEY);
    }

    public function getPhone(): ?string
    {
        return $this->getData(self::PHONE_KEY);
    }

    public function getStreet(): ?string
    {
        $streetLines = $this->getData(self::STREET_KEY) ?? [];

        return implode(', ', $streetLines);
    }

    public function getCompany(): ?string
    {
        return $this->getData(self::COMPANY_KEY);
    }
}

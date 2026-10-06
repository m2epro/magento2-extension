<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Amazon\Order;

class BillingAddress extends \Ess\M2ePro\Model\Order\ShippingAddress
{
    public const DATA_KEY_COUNTRY_CODE = 'country_code';
    public const DATA_KEY_STATE = 'country_state';
    public const DATA_KEY_CITY = 'city';
    public const DATA_KEY_POSTAL_CODE = 'postal_code';
    public const DATA_KEY_STREET = 'street';

    private const DEFAULT_POSTAL_CODE = '0000';

    public function getCountryId()
    {
        return $this->getData(self::DATA_KEY_COUNTRY_CODE);
    }

    public function getState()
    {
        return $this->getData(self::DATA_KEY_STATE);
    }

    /**
     * @return string[]
     */
    public function getStreet(): array
    {
        return array_filter($this->getData(self::DATA_KEY_STREET));
    }

    public function getRawData(): array
    {
        return [
            'country_id' => $this->getCountryId(),
            'region' => $this->getState(),
            'city' => $this->getCity(),
            'postcode' => $this->getPostalCode(),
            'street' => $this->getStreet(),
        ];
    }

    protected function isRegionOverrideRequired(): bool
    {
        /** @var \Ess\M2ePro\Model\Amazon\Account $account */
        $account = $this->order->getAccount()->getChildObject();

        return $account->isRegionOverrideRequired();
    }

    public function getCity(): string
    {
        if ($this->getData(self::DATA_KEY_CITY)) {
            return $this->getData(self::DATA_KEY_CITY);
        }

        return $this->getCountryName();
    }

    public function getPostalCode()
    {
        $postalCode = $this->getData(self::DATA_KEY_POSTAL_CODE);

        if (empty($postalCode)) {
            $postalCode = self::DEFAULT_POSTAL_CODE;
        }

        return $postalCode;
    }
}

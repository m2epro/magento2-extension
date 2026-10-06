<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Order;

class BillingAddress extends \Ess\M2ePro\Model\Order\ShippingAddress
{
    public const DATA_KEY_COUNTRY_CODE = 'country_code';
    public const DATA_KEY_STATE = 'country_state';
    public const DATA_KEY_CITY = 'city';
    public const DATA_KEY_POSTAL_CODE = 'postal_code';
    public const DATA_KEY_STREET = 'street';

    private const DEFAULT_POSTAL_CODE = '0000';

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

    public function getCountryId()
    {
        return $this->getData(self::DATA_KEY_COUNTRY_CODE);
    }

    public function getCity()
    {
        if ($this->getData(self::DATA_KEY_CITY)) {
            return $this->getData(self::DATA_KEY_CITY);
        }

        return $this->getCountryName();
    }

    public function getState(): string
    {
        return (string)$this->getData(self::DATA_KEY_STATE);
    }

    public function getPostalCode()
    {
        $postalCode = $this->getData(self::DATA_KEY_POSTAL_CODE);

        if (empty($postalCode)) {
            $postalCode = self::DEFAULT_POSTAL_CODE;
        }

        return $postalCode;
    }

    /**
     * @return string[]
     */
    public function getStreet(): array
    {
        return array_filter($this->getData(self::DATA_KEY_STREET));
    }

    protected function isRegionOverrideRequired(): bool
    {
        /** @var \Ess\M2ePro\Model\Ebay\Account $account */
        $account = $this->order->getAccount()->getChildObject();

        return $account->isRegionOverrideRequired();
    }
}

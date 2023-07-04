<?php

namespace Ess\M2ePro\Api\Ebay\Data\Order\ShippingDetails;

interface AddressInterface
{
    public const COUNTRY_CODE_KEY = 'country_code';
    public const COUNTRY_NAME_KEY = 'country_name';
    public const CITY_KEY = 'city';
    public const STATE_KEY = 'state';
    public const POSTAL_CODE_KEY = 'postal_code';
    public const PHONE_KEY = 'phone';
    public const STREET_KEY = 'street';
    public const COMPANY_KEY = 'company';

    /**
     * @return string|null
     */
    public function getCountryCode(): ?string;

    /**
     * @return string|null
     */
    public function getCountryName(): ?string;

    /**
     * @return string|null
     */
    public function getCity(): ?string;

    /**
     * @return string|null
     */
    public function getState(): ?string;

    /**
     * @return string|null
     */
    public function getPostalCode(): ?string;

    /**
     * @return string|null
     */
    public function getPhone(): ?string;

    /**
     * @return string|null
     */
    public function getStreet(): ?string;

    /**
     * @return string|null
     */
    public function getCompany(): ?string;
}

<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Amazon\Connector\Orders\Get\BillingAddress;

class ResponseItem
{
    public string $amazonOrderId;
    public string $country;
    public string $city;
    public string $state;
    public string $postalCode;
    /** @var string[] */
    public array $addressLines;

    /**
     * @param string[] $addressLines
     */
    public function __construct(
        string $amazonOrderId,
        string $country,
        string $city,
        string $state,
        string $postalCode,
        array $addressLines
    ) {
        $this->amazonOrderId = $amazonOrderId;
        $this->country = $country;
        $this->city = $city;
        $this->state = $state;
        $this->postalCode = $postalCode;
        $this->addressLines = $addressLines;
    }
}

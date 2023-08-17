<?php

namespace Ess\M2ePro\Api\Ebay\Data\Order;

use Ess\M2ePro\Api\Ebay\Data\Order\ShippingDetails;

interface ShippingDetailsInterface
{
    public const ADDRESS_KEY = 'address';
    public const SERVICE_KEY = 'service';
    public const PRICE_KEY = 'price';
    public const DATE_KEY = 'date';
    public const GLOBAL_SHIPPING_DETAILS_KEY = 'global_shipping_details';
    public const CLICK_AND_COLLECT_DETAILS_KEY = 'click_and_collect_details';
    public const IN_STORE_PICKUP_DETAILS_KEY = 'in_store_pickup_details';

    /**
     * @return \Ess\M2ePro\Api\Ebay\Data\Order\ShippingDetails\AddressInterface
     */
    public function getAddress(): ShippingDetails\AddressInterface;

    /**
     * @return string|null
     */
    public function getService(): ?string;

    /**
     * @return float
     */
    public function getPrice(): float;

    /**
     * @return string|null
     */
    public function getDate(): ?string;

    /**
     * @return \Ess\M2ePro\Api\Ebay\Data\Order\ShippingDetails\GlobalShippingDetailsInterface
     */
    public function getGlobalShippingDetails(): ShippingDetails\GlobalShippingDetailsInterface;

    /**
     * @return \Ess\M2ePro\Api\Ebay\Data\Order\ShippingDetails\ClickAndCollectDetailsInterface
     */
    public function getClickAndCollectDetails(): ShippingDetails\ClickAndCollectDetailsInterface;

    /**
     * @return \Ess\M2ePro\Api\Ebay\Data\Order\ShippingDetails\InStorePickupDetailsInterface
     */
    public function getInStorePickupDetails(): ShippingDetails\InStorePickupDetailsInterface;
}

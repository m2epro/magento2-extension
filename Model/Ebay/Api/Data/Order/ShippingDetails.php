<?php

namespace Ess\M2ePro\Model\Ebay\Api\Data\Order;

use Ess\M2ePro\Api\Ebay\Data\Order\ShippingDetails as DetailsInterfaces;

class ShippingDetails extends \Ess\M2ePro\Api\DataObject implements
    \Ess\M2ePro\Api\Ebay\Data\Order\ShippingDetailsInterface
{
    /** @var \Ess\M2ePro\Api\Ebay\Data\Order\ShippingDetails\AddressInterfaceFactory */
    private $addressFactory;
    /** @var \Ess\M2ePro\Api\Ebay\Data\Order\ShippingDetails\ClickAndCollectDetailsInterfaceFactory */
    private $clickAndCollectDetailsFactory;
    /** @var \Ess\M2ePro\Api\Ebay\Data\Order\ShippingDetails\GlobalShippingDetailsInterfaceFactory */
    private $globalShippingDetailsFactory;
    /** @var \Ess\M2ePro\Api\Ebay\Data\Order\ShippingDetails\InStorePickupDetailsInterfaceFactory */
    private $inStorePickupDetailsFactory;

    public function __construct(
        DetailsInterfaces\AddressInterfaceFactory $addressFactory,
        DetailsInterfaces\ClickAndCollectDetailsInterfaceFactory $clickAndCollectDetailsFactory,
        DetailsInterfaces\GlobalShippingDetailsInterfaceFactory $globalShippingDetailsFactory,
        DetailsInterfaces\InStorePickupDetailsInterfaceFactory $inStorePickupDetailsFactory,
        array $data = []
    ) {
        parent::__construct($data);
        $this->addressFactory = $addressFactory;
        $this->clickAndCollectDetailsFactory = $clickAndCollectDetailsFactory;
        $this->globalShippingDetailsFactory = $globalShippingDetailsFactory;
        $this->inStorePickupDetailsFactory = $inStorePickupDetailsFactory;
    }

    public function getService(): ?string
    {
        return $this->getData(self::SERVICE_KEY);
    }

    public function getPrice(): float
    {
        return (float)$this->getData(self::PRICE_KEY);
    }

    public function getDate(): ?string
    {
        return $this->getData(self::DATE_KEY);
    }

    public function getGlobalShippingDetails(): DetailsInterfaces\GlobalShippingDetailsInterface
    {
        $globalShipping = $this->globalShippingDetailsFactory->create();
        $globalShipping->setData($this->getDecodedJsonData(self::GLOBAL_SHIPPING_DETAILS_KEY));

        return $globalShipping;
    }

    public function getClickAndCollectDetails(): DetailsInterfaces\ClickAndCollectDetailsInterface
    {
        $clickAndCollect = $this->clickAndCollectDetailsFactory->create();
        $clickAndCollect->setData($this->getDecodedJsonData(self::CLICK_AND_COLLECT_DETAILS_KEY));

        return $clickAndCollect;
    }

    public function getInStorePickupDetails(): DetailsInterfaces\InStorePickupDetailsInterface
    {
        $inStorePickUp = $this->inStorePickupDetailsFactory->create();
        $inStorePickUp->setData($this->getDecodedJsonData(self::IN_STORE_PICKUP_DETAILS_KEY));

        return $inStorePickUp;
    }

    public function getAddress(): \Ess\M2ePro\Api\Ebay\Data\Order\ShippingDetails\AddressInterface
    {
        $address = $this->addressFactory->create();
        $address->setData($this->getDecodedJsonData(self::ADDRESS_KEY));

        return $address;
    }
}

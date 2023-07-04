<?php

namespace Ess\M2ePro\Model\Ebay\Api\Data\Order;

class OrderItem extends \Ess\M2ePro\Api\DataObject implements \Ess\M2ePro\Api\Ebay\Data\Order\OrderItemInterface
{
    /** @var \Ess\M2ePro\Api\Ebay\Data\Order\OrderItem\TaxDetailsInterfaceFactory */
    private $taxDetailsFactory;
    /** @var \Ess\M2ePro\Api\Ebay\Data\Order\OrderItem\TrackingDetailsInterfaceFactory */
    private $trackingDetailsFactory;

    public function __construct(
        \Ess\M2ePro\Api\Ebay\Data\Order\OrderItem\TaxDetailsInterfaceFactory $taxDetailsFactory,
        \Ess\M2ePro\Api\Ebay\Data\Order\OrderItem\TrackingDetailsInterfaceFactory $trackingDetailsFactory,
        array $data = []
    ) {
        parent::__construct($data);
        $this->taxDetailsFactory = $taxDetailsFactory;
        $this->trackingDetailsFactory = $trackingDetailsFactory;
    }

    public function getId(): int
    {
        return (int)$this->getData(self::ID_KEY);
    }

    public function getMagentoProductId(): int
    {
        return (int)$this->getData(self::MAGENTO_PRODUCT_ID_KEY);
    }

    public function getCreateDate(): ?string
    {
        return $this->getData(self::CREATE_DATE_KEY);
    }

    public function getUpdateDate(): ?string
    {
        return $this->getData(self::UPDATE_DATE_KEY);
    }

    public function getTransactionId(): ?string
    {
        return $this->getData(self::ID_KEY);
    }

    public function getSellingManagerId(): ?string
    {
        return $this->getData(self::SELLING_MANAGER_ID_KEY);
    }

    public function getEbayItemId(): ?string
    {
        return $this->getData(self::EBAY_ITEM_ID_KEY);
    }

    public function getTitle(): ?string
    {
        return $this->getData(self::TITLE_KEY);
    }

    public function getSku(): ?string
    {
        return $this->getData(self::SKU_KEY);
    }

    public function getPrice(): float
    {
        return (float)$this->getData(self::PRICE_KEY);
    }

    public function getTaxDetails(): \Ess\M2ePro\Api\Ebay\Data\Order\OrderItem\TaxDetailsInterface
    {
        $taxDetails = $this->taxDetailsFactory->create();
        $taxDetails->addData($this->getDecodedJsonData(self::TAX_DETAILS_KEY));

        return $taxDetails;
    }

    public function getTrackingDetails(): array
    {
        $trackingDetailsData = $this->getDecodedJsonData(self::TRACKING_DETAILS_KEY);
        $trackingDetails = [];
        foreach ($trackingDetailsData as $trackingItemData) {
            $trackingItem = $this->trackingDetailsFactory->create();
            $trackingItem->addData($trackingItemData);

            $trackingDetails[] = $trackingItem;
        }

        return $trackingDetails;
    }

    public function getFinalFee(): float
    {
        return (float)$this->getData(self::FINAL_FEE_KEY);
    }

    public function getWasteRecyclingFee(): float
    {
        return (float)$this->getData(self::WASTE_RECYCLING_FEE_KEY);
    }
}

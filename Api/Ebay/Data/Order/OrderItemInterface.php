<?php

namespace Ess\M2ePro\Api\Ebay\Data\Order;

interface OrderItemInterface
{
    public const ID_KEY = 'id';
    public const MAGENTO_PRODUCT_ID_KEY = 'magento_product_id';
    public const CREATE_DATE_KEY = 'create_date';
    public const UPDATE_DATE_KEY = 'update_date';
    public const TRANSACTION_ID_KEY = 'transaction_id';
    public const SELLING_MANAGER_ID_KEY = 'selling_manager_id';
    public const EBAY_ITEM_ID_KEY = 'ebay_item_id';
    public const TITLE_KEY = 'title';
    public const SKU_KEY = 'sku';
    public const PRICE_KEY = 'price';
    public const TAX_DETAILS_KEY = 'tax_details';
    public const TRACKING_DETAILS_KEY = 'tracking_details';
    public const FINAL_FEE_KEY = 'final_fee';
    public const WASTE_RECYCLING_FEE_KEY = 'waste_recycling_fee';

    /**
     * @return int
     */
    public function getId(): int;

    /**
     * @return int
     */
    public function getMagentoProductId(): int;

    /**
     * @return string|null
     */
    public function getCreateDate(): ?string;

    /**
     * @return string|null
     */
    public function getUpdateDate(): ?string;

    /**
     * @return string|null
     */
    public function getTransactionId(): ?string;

    /**
     * @return string|null
     */
    public function getSellingManagerId(): ?string;

    /**
     * @return string|null
     */
    public function getEbayItemId(): ?string;

    /**
     * @return string|null
     */
    public function getTitle(): ?string;

    /**
     * @return string|null
     */
    public function getSku(): ?string;

    /**
     * @return float
     */
    public function getPrice(): float;

    /**
     * @return \Ess\M2ePro\Api\Ebay\Data\Order\OrderItem\TaxDetailsInterface
     */
    public function getTaxDetails(): \Ess\M2ePro\Api\Ebay\Data\Order\OrderItem\TaxDetailsInterface;

    /**
     * @return \Ess\M2ePro\Api\Ebay\Data\Order\OrderItem\TrackingDetailsInterface[]
     */
    public function getTrackingDetails(): array;

    /**
     * @return float
     */
    public function getFinalFee(): float;

    /**
     * @return float
     */
    public function getWasteRecyclingFee(): float;
}

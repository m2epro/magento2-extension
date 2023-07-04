<?php

namespace Ess\M2ePro\Api\Ebay\Data;

interface OrderInterface
{
    public const ID_KEY = 'id';
    public const EBAY_ORDER_ID_KEY = 'ebay_order_id';
    public const SELLING_MANAGER_ID_KEY = 'selling_manager_id';
    public const BUYER_KEY = 'buyer';
    public const PAID_AMOUNT_KEY = 'paid_amount';
    public const CURRENCY_KEY = 'currency';
    public const SAVED_AMOUNT_KEY = 'saved_amount';
    public const FINAL_FEE_KEY = 'final_fee';
    public const SHIPPING_DETAILS_KEY = 'shipping_details';
    public const PAYMENT_DETAILS_KEY = 'payment_details';
    public const TAX_DETAILS_KEY = 'tax_details';
    public const TAX_REFERENCE_KEY = 'tax_reference';
    public const SHIPPING_DATE_TO_KEY = 'shipping_date_to';
    public const PURCHASE_CREATE_DATE_KEY = 'purchase_create_date';
    public const PURCHASE_UPDATE_DATE_KEY = 'purchase_update_date';
    public const ACCOUNT_ID_KEY = 'account_id';
    public const CREATE_DATE_KEY = 'create_date';
    public const UPDATE_DATE_KEY = 'update_date';
    public const MARKETPLACE_CODE_KEY = 'marketplace_code';
    public const ORDER_ITEMS_KEY = 'order_items';

    /**
     * @return int
     */
    public function getId(): int;

    /**
     * @return string|null
     */
    public function getEbayOrderId(): ?string;

    /**
     * @return string|null
     */
    public function getSellingManagerId(): ?string;

    /**
     * @return float
     */
    public function getPaidAmount(): float;

    /**
     * @return string|null
     */
    public function getCurrency(): ?string;

    /**
     * @return float
     */
    public function getSavedAmount(): float;

    /**
     * @return float
     */
    public function fetFinalFee(): float;

    /**
     * @return \Ess\M2ePro\Api\Ebay\Data\Order\ShippingDetailsInterface
     */
    public function getShippingDetails(): Order\ShippingDetailsInterface;

    /**
     * @return \Ess\M2ePro\Api\Ebay\Data\Order\PaymentDetailsInterface
     */
    public function getPaymentDetails(): Order\PaymentDetailsInterface;

    /**
     * @return \Ess\M2ePro\Api\Ebay\Data\Order\BuyerInterface
     */
    public function getBuyer(): Order\BuyerInterface;

    /**
     * @return \Ess\M2ePro\Api\Ebay\Data\Order\TaxDetailsInterface
     */
    public function getTaxDetails(): Order\TaxDetailsInterface;

    /**
     * @return string|null
     */
    public function getTaxReference(): ?string;

    /**
     * @return string|null
     */
    public function getShippingDateTo(): ?string;

    /**
     * @return string|null
     */
    public function getPurchaseCreateDate(): ?string;

    /**
     * @return string|null
     */
    public function getPurchaseUpdateDate(): ?string;

    /**
     * @return int
     */
    public function getAccountId(): int;

    /**
     * @return string|null
     */
    public function getCreateDate(): ?string;

    /**
     * @return string|null
     */
    public function getUpdateDate(): ?string;

    /**
     * @return string
     */
    public function getMarketplaceCode(): string;

    /**
     * @return \Ess\M2ePro\Api\Ebay\Data\Order\OrderItemInterface[]
     */
    public function getOrderItems(): array;
}

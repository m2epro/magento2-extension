<?php

namespace Ess\M2ePro\Model\Ebay\Api\Data;

class Order extends \Ess\M2ePro\Api\DataObject implements \Ess\M2ePro\Api\Ebay\Data\OrderInterface
{
    /** @var \Ess\M2ePro\Api\Ebay\Data\Order\BuyerInterfaceFactory */
    private $buyerFactory;
    /** @var \Ess\M2ePro\Api\Ebay\Data\Order\ShippingDetailsInterfaceFactory */
    private $shippingDetailsFactory;
    /** @var \Ess\M2ePro\Api\Ebay\Data\Order\PaymentDetailsInterfaceFactory */
    private $paymentDetailsFactory;
    /** @var \Ess\M2ePro\Api\Ebay\Data\Order\TaxDetailsInterfaceFactory */
    private $taxDetailsFactory;
    /** @var \Ess\M2ePro\Api\Ebay\Data\Order\OrderItemInterfaceFactory */
    private $orderItemFactory;

    public function __construct(
        \Ess\M2ePro\Api\Ebay\Data\Order\BuyerInterfaceFactory $buyerFactory,
        \Ess\M2ePro\Api\Ebay\Data\Order\ShippingDetailsInterfaceFactory $shippingDetailsFactory,
        \Ess\M2ePro\Api\Ebay\Data\Order\PaymentDetailsInterfaceFactory $paymentDetailsFactory,
        \Ess\M2ePro\Api\Ebay\Data\Order\TaxDetailsInterfaceFactory $taxDetailsFactory,
        \Ess\M2ePro\Api\Ebay\Data\Order\OrderItemInterfaceFactory $orderItemFactory,
        array $data = []
    ) {
        parent::__construct($data);
        $this->buyerFactory = $buyerFactory;
        $this->shippingDetailsFactory = $shippingDetailsFactory;
        $this->paymentDetailsFactory = $paymentDetailsFactory;
        $this->taxDetailsFactory = $taxDetailsFactory;
        $this->orderItemFactory = $orderItemFactory;
    }

    public function getId(): int
    {
        return (int)$this->getData(self::ID_KEY);
    }

    public function getEbayOrderId(): ?string
    {
        return $this->getData(self::EBAY_ORDER_ID_KEY);
    }

    public function getSellingManagerId(): ?string
    {
        return $this->getData(self::SELLING_MANAGER_ID_KEY);
    }

    public function getPaidAmount(): float
    {
        return (float)$this->getData(self::PAID_AMOUNT_KEY);
    }

    public function getCurrency(): ?string
    {
        return $this->getData(self::CURRENCY_KEY);
    }

    public function getSavedAmount(): float
    {
        return (float)$this->getData(self::SAVED_AMOUNT_KEY);
    }

    public function fetFinalFee(): float
    {
        return (float)$this->getData(self::FINAL_FEE_KEY);
    }

    public function getShippingDetails(): \Ess\M2ePro\Api\Ebay\Data\Order\ShippingDetailsInterface
    {
        $shippingDetails = $this->shippingDetailsFactory->create();
        $shippingDetails->addData($this->getDecodedJsonData(self::SHIPPING_DETAILS_KEY));

        return $shippingDetails;
    }

    public function getPaymentDetails(): \Ess\M2ePro\Api\Ebay\Data\Order\PaymentDetailsInterface
    {
        $paymentDetails = $this->paymentDetailsFactory->create();
        $paymentDetails->addData($this->getDecodedJsonData(self::PAYMENT_DETAILS_KEY));

        return $paymentDetails;
    }

    public function getBuyer(): \Ess\M2ePro\Api\Ebay\Data\Order\BuyerInterface
    {
        $buyer = $this->buyerFactory->create();
        $buyer->addData($this->getDecodedJsonData(self::BUYER_KEY));

        return $buyer;
    }

    public function getTaxDetails(): \Ess\M2ePro\Api\Ebay\Data\Order\TaxDetailsInterface
    {
        $taxDetails = $this->taxDetailsFactory->create();
        $taxDetails->addData($this->getDecodedJsonData(self::TAX_DETAILS_KEY));

        return $taxDetails;
    }

    public function getTaxReference(): ?string
    {
        return $this->getData(self::TAX_REFERENCE_KEY);
    }

    public function getShippingDateTo(): ?string
    {
        return $this->getData(self::SHIPPING_DATE_TO_KEY);
    }

    public function getPurchaseCreateDate(): ?string
    {
        return $this->getData(self::PURCHASE_CREATE_DATE_KEY);
    }

    public function getPurchaseUpdateDate(): ?string
    {
        return $this->getData(self::PURCHASE_UPDATE_DATE_KEY);
    }

    public function getAccountId(): int
    {
        return (int)$this->getData(self::ACCOUNT_ID_KEY);
    }

    public function getCreateDate(): ?string
    {
        return $this->getData(self::CREATE_DATE_KEY);
    }

    public function getUpdateDate(): ?string
    {
        return $this->getData(self::UPDATE_DATE_KEY);
    }

    public function getMarketplaceCode(): string
    {
        return (string)$this->getData(self::MARKETPLACE_CODE_KEY);
    }

    public function getOrderItems(): array
    {
        $orderItemsData = $this->getDecodedJsonData(self::ORDER_ITEMS_KEY);
        $orderItems = [];
        foreach ($orderItemsData as $orderItemData) {
            $orderItem = $this->orderItemFactory->create();
            $orderItem->addData($orderItemData);

            $orderItems[] = $orderItem;
        }

        return $orderItems;
    }
}

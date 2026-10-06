<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Amazon\Order;

use Ess\M2ePro\Model\ResourceModel\Amazon\Order as AmazonOrderResource;
use Ess\M2ePro\Model\ResourceModel\Order as OrderResource;

class Repository
{
    private \Ess\M2ePro\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory;

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory
    ) {
        $this->orderCollectionFactory = $orderCollectionFactory;
    }

    public function findByMagentoOrderId(int $id): ?\Ess\M2ePro\Model\Order
    {
        $collection = $this->orderCollectionFactory->createWithAmazonChildMode();
        $collection->addFieldToFilter(OrderResource::COLUMN_MAGENTO_ORDER_ID, $id);

        /** @var \Ess\M2ePro\Model\Order|false $entity */
        $entity = $collection->fetchItem();
        if ($entity === false) {
            return null;
        }

        return $entity;
    }

    public function findByAmazonOrderId(string $amazonOrderId): ?\Ess\M2ePro\Model\Order
    {
        $collection = $this->orderCollectionFactory->createWithAmazonChildMode();
        $collection->addFieldToFilter(
            AmazonOrderResource::COLUMN_AMAZON_ORDER_ID,
            $amazonOrderId
        );

        /** @var \Ess\M2ePro\Model\Order $order */
        $order = $collection->getFirstItem();
        if ($order->isObjectNew()) {
            return null;
        }

        return $order;
    }

    /**
     * @param string[] $amazonOrderIds
     *
     * @return array<string, \Ess\M2ePro\Model\Order>
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function findByAmazonOrderIds(array $amazonOrderIds): array
    {
        $collection = $this->orderCollectionFactory->createWithAmazonChildMode();
        $collection->addFieldToFilter(
            AmazonOrderResource::COLUMN_AMAZON_ORDER_ID,
            ['in' => $amazonOrderIds]
        );

        $result = [];
        foreach ($collection->getItems() as $order) {
            $result[$order->getChildObject()->getAmazonOrderId()] = $order;
        }

        return $result;
    }

    /**
     * @return \Ess\M2ePro\Model\Order[]
     */
    public function retrieveWithoutMagentoOrders(
        int $accountId,
        \DateTime $fromPurchaseDate,
        \DateTime $toPurchaseDate
    ): array {
        $collection = $this->orderCollectionFactory
            ->createWithAmazonChildMode()
            ->addFieldToFilter(
                AmazonOrderResource::COLUMN_PURCHASE_CREATE_DATE,
                ['gteq' => $fromPurchaseDate->format('Y-m-d H:i:s')]
            )
            ->addFieldToFilter(
                AmazonOrderResource::COLUMN_PURCHASE_CREATE_DATE,
                ['lteq' => $toPurchaseDate->format('Y-m-d H:i:s')]
            )
            ->addFieldToFilter(
                OrderResource::COLUMN_MAGENTO_ORDER_ID,
                ['null' => true]
            )
            ->addFieldToFilter(
                OrderResource::COLUMN_ACCOUNT_ID,
                ['eq' => $accountId]
            );

        return array_values($collection->getItems());
    }

    public function findOlderForReceiveBillingAddress(int $accountId): ?\Ess\M2ePro\Model\Order
    {
        $collection = $this->orderCollectionFactory->createWithAmazonChildMode();
        $collection->addFieldToFilter(
            OrderResource::COLUMN_ACCOUNT_ID,
            ['eq' => $accountId]
        );
        $collection->addFieldToFilter(
            OrderResource::COLUMN_MAGENTO_ORDER_ID,
            ['null' => true]
        );

        $collection->addFieldToFilter(
            'status',
            ['neq' => \Ess\M2ePro\Model\Amazon\Order::STATUS_CANCELED]
        );

        $dateFrom = \Ess\M2ePro\Helper\Date::createCurrentGmt()->modify('-1 month');
        $dateTo = \Ess\M2ePro\Helper\Date::createCurrentGmt()->modify('-20 minutes');

        $collection->addFieldToFilter(
            AmazonOrderResource::COLUMN_PURCHASE_CREATE_DATE,
            ['gteq' => $dateFrom->format('Y-m-d H:i:s')]
        );
        $collection->addFieldToFilter(
            AmazonOrderResource::COLUMN_PURCHASE_CREATE_DATE,
            ['lteq' => $dateTo->format('Y-m-d H:i:s')]
        );
        $collection->addOrder(
            AmazonOrderResource::COLUMN_PURCHASE_CREATE_DATE,
            \Magento\Framework\Data\Collection::SORT_ORDER_ASC
        );
        $collection->getSelect()->limit(1);

        $result = $collection->getFirstItem();
        if ($result->isObjectNew()) {
            return null;
        }

        return $result;
    }
}

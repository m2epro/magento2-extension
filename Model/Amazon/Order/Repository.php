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
}

<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Amazon\Account;

class Repository
{
    /** @var \Ess\M2ePro\Model\ResourceModel\Account\CollectionFactory */
    private $collectionFactory;

    public function __construct(\Ess\M2ePro\Model\ResourceModel\Amazon\Account\CollectionFactory $collectionFactory)
    {
        $this->collectionFactory = $collectionFactory;
    }

    public function isExistsWithMerchantId(string $merchantId): bool
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter(
            \Ess\M2ePro\Model\ResourceModel\Amazon\Account::COLUMN_MERCHANT_ID,
            $merchantId
        );
        $collection->getSelect()->limit(1);

        return !$collection->getFirstItem()->isEmpty();
    }
}

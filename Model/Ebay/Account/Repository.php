<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Account;

class Repository
{
    private \Ess\M2ePro\Model\ResourceModel\Account\CollectionFactory $accountCollectionFactory;

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Account\CollectionFactory $accountCollectionFactory
    ) {
        $this->accountCollectionFactory = $accountCollectionFactory;
    }

    /**
     * @return \Ess\M2ePro\Model\Account[]
     */
    public function getAll(): array
    {
        $accountsCollection = $this->accountCollectionFactory->createWithEbayChildMode();

        return array_values($accountsCollection->getItems());
    }
}

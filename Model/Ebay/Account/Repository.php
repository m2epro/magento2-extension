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

    public function getByAccountId(int $accountId): \Ess\M2ePro\Model\Ebay\Account
    {
        $accountsCollection = $this->accountCollectionFactory->createWithEbayChildMode();
        $accountsCollection->addFieldToFilter(
            'account_id',
            ['eq' => $accountId]
        );

        $account = $accountsCollection->getFirstItem();
        if ($account->isObjectNew()) {
            throw new \Ess\M2ePro\Model\Exception\Logic(
                sprintf('Not found eBay Account by Account ID %s', $accountId)
            );
        }

        /** @var \Ess\M2ePro\Model\Ebay\Account */
        return $account->getChildObject();
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

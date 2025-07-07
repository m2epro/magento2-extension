<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Walmart\Account;

use Ess\M2ePro\Model\ResourceModel\Walmart\Account as AccountResource;

class Repository
{
    private \Ess\M2ePro\Model\ResourceModel\Account\CollectionFactory $accountCollectionFactory;

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Account\CollectionFactory $accountCollectionFactory
    ) {
        $this->accountCollectionFactory = $accountCollectionFactory;
    }

    public function find(int $accountId): ?\Ess\M2ePro\Model\Account
    {
        $collection = $this->accountCollectionFactory->createWithWalmartChildMode();
        $collection->addFieldToFilter(
            \Ess\M2ePro\Model\ResourceModel\Walmart\Account::COLUMN_ACCOUNT_ID,
            ['eq' => $accountId]
        );

        $account = $collection->getFirstItem();

        if ($account->isObjectNew()) {
            return null;
        }

        return $account;
    }

    public function get(int $id): \Ess\M2ePro\Model\Account
    {
        $account = $this->find($id);
        if ($account === null) {
            throw new \LogicException("Account '$id' not found.");
        }

        return $account;
    }

    public function isAccountExists(int $id): bool
    {
        return $this->find($id) !== null;
    }

    public function findByIdentifier(string $identifier): ?\Ess\M2ePro\Model\Account
    {
        $collection = $this->accountCollectionFactory->createWithWalmartChildMode();
        $collection->addFieldToFilter(AccountResource::COLUMN_IDENTIFIER, $identifier);

        $account = $collection->getFirstItem();
        if ($account->isObjectNew()) {
            return null;
        }

        return $account;
    }

    /**
     * @return \Ess\M2ePro\Model\Account[]
     */
    public function getAllItems(): array
    {
        $collection = $this->accountCollectionFactory->createWithWalmartChildMode();

        return array_values($collection->getItems());
    }
}

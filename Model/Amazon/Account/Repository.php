<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Amazon\Account;

class Repository
{
    /** @var \Ess\M2ePro\Model\ResourceModel\Amazon\Account\CollectionFactory */
    private $collectionFactory;
    /** @var bool */
    private $isLoaded = false;
    /** @var array<int, \Ess\M2ePro\Model\Amazon\Account> */
    private $entitiesById = [];
    /** @var array<string, \Ess\M2ePro\Model\Amazon\Account[]> */
    private $entitiesGroupByMerchantId = [];

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Amazon\Account\CollectionFactory $collectionFactory
    ) {
        $this->collectionFactory = $collectionFactory;
    }

    public function find(int $accountId): ?\Ess\M2ePro\Model\Amazon\Account
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter(
            \Ess\M2ePro\Model\ResourceModel\Amazon\Account::COLUMN_ACCOUNT_ID,
            ['eq' => $accountId]
        );

        $account = $collection->getFirstItem();

        if ($account->isObjectNew()) {
            return null;
        }

        return $account;
    }

    /**
     * @return \Ess\M2ePro\Model\Amazon\Account[]
     */
    public function findAllWithEnabledFbaInventory(): array
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter(
            \Ess\M2ePro\Model\ResourceModel\Amazon\Account::COLUMN_FBA_INVENTORY_MODE,
            ['eq' => 1]
        );

        return array_values($collection->getItems());
    }

    public function findWithEnabledFbaInventoryByMerchantId(string $merchantId): ?\Ess\M2ePro\Model\Amazon\Account
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter(
            \Ess\M2ePro\Model\ResourceModel\Amazon\Account::COLUMN_MERCHANT_ID,
            ['eq' => $merchantId]
        );
        $collection->addFieldToFilter(
            \Ess\M2ePro\Model\ResourceModel\Amazon\Account::COLUMN_FBA_INVENTORY_MODE,
            ['eq' => 1]
        );
        $collection->setPageSize(1);

        $account = $collection->getFirstItem();

        return $account->isObjectNew() ? null : $account;
    }

    public function isExistsWithMerchantId(string $merchantId): bool
    {
        return !empty($this->retrieveByMerchantId($merchantId));
    }

    /**
     * @return \Ess\M2ePro\Model\Amazon\Account[]
     */
    public function retrieveByMerchantId(string $merchantId): array
    {
        $this->load();

        return $this->entitiesGroupByMerchantId[$merchantId] ?? [];
    }

    /**
     * @return int[]
     */
    public function retrieveEntityIdsByMerchantId(string $merchantId): array
    {
        $ids = [];
        foreach ($this->retrieveByMerchantId($merchantId) as $account) {
            $ids[] = (int)$account->getId();
        }

        return $ids;
    }

    public function getFistByMerchantId(string $merchantId): \Ess\M2ePro\Model\Amazon\Account
    {
        $accounts = $this->retrieveByMerchantId($merchantId);

        return reset($accounts);
    }

    /**
     * @return \Ess\M2ePro\Model\Amazon\Account[]
     */
    public function getAll(): array
    {
        $this->load();

        return array_values($this->entitiesById);
    }

    /**
     * @return array<string, \Ess\M2ePro\Model\Amazon\Account[]>
     */
    public function getAllGroupedByMerchantId(): array
    {
        $this->load();

        return $this->entitiesGroupByMerchantId;
    }

    private function load(): void
    {
        if ($this->isLoaded) {
            return;
        }

        $this->entitiesById = [];
        $this->entitiesGroupByMerchantId = [];

        $collection = $this->collectionFactory->create();
        foreach ($collection->getItems() as $entity) {
            $this->entitiesById[$entity->getId()] = $entity;
            $this->entitiesGroupByMerchantId[$entity->getMerchantId()][] = $entity;
        }

        $this->isLoaded = true;
    }
}

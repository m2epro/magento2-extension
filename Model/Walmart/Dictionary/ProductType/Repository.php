<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Walmart\Dictionary\ProductType;

use Ess\M2ePro\Model\ResourceModel\Walmart\Dictionary\ProductType as ProductTypeDictionaryResource;

class Repository
{
    private \Ess\M2ePro\Model\ResourceModel\Walmart\Dictionary\ProductType\CollectionFactory $collectionFactory;
    private \Ess\M2ePro\Model\ResourceModel\Walmart\Dictionary\ProductType $productTypeDictionaryResource;
    private \Ess\M2ePro\Model\Walmart\Dictionary\ProductTypeFactory $productTypeDictionaryFactory;

    private array $runtimeCache = [];

    public function __construct(
        \Ess\M2ePro\Model\Walmart\Dictionary\ProductTypeFactory $productTypeDictionaryFactory,
        \Ess\M2ePro\Model\ResourceModel\Walmart\Dictionary\ProductType\CollectionFactory $collectionFactory,
        \Ess\M2ePro\Model\ResourceModel\Walmart\Dictionary\ProductType $productTypeDictionaryResource
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->productTypeDictionaryResource = $productTypeDictionaryResource;
        $this->productTypeDictionaryFactory = $productTypeDictionaryFactory;
    }

    public function find(int $id): ?\Ess\M2ePro\Model\Walmart\Dictionary\ProductType
    {
        if (($model = $this->tryGetFromRuntimeCache($id)) !== null) {
            return $model;
        }

        $model = $this->productTypeDictionaryFactory->createEmpty();
        $this->productTypeDictionaryResource->load($model, $id);

        if ($model->isObjectNew()) {
            return null;
        }

        $this->addToRuntimeCache($model);

        return $model;
    }

    public function get(int $id): \Ess\M2ePro\Model\Walmart\Dictionary\ProductType
    {
        $model = $this->find($id);
        if ($model === null) {
            throw new \Ess\M2ePro\Model\Exception\EntityNotFound("Product Type Dictionary with id $id not found.");
        }

        return $model;
    }

    /**
     * @return \Ess\M2ePro\Model\Walmart\Dictionary\ProductType[]
     */
    public function getAll(): array
    {
        $collection = $this->collectionFactory->create();

        return array_values($collection->getItems());
    }

    public function findByNick(string $nick, int $marketplaceId): ?\Ess\M2ePro\Model\Walmart\Dictionary\ProductType
    {
        $collection = $this->collectionFactory->create();

        $collection->addFieldToFilter(ProductTypeDictionaryResource::COLUMN_NICK, $nick);
        $collection->addFieldToFilter(ProductTypeDictionaryResource::COLUMN_MARKETPLACE_ID, $marketplaceId);

        /** @var \Ess\M2ePro\Model\Walmart\Dictionary\ProductType $entity */
        $entity = $collection->getFirstItem();
        if ($entity->isObjectNew()) {
            return null;
        }

        return $entity;
    }

    /**
     * @return \Ess\M2ePro\Model\Walmart\Dictionary\ProductType[]
     */
    public function retrieveByMarketplace(
        \Ess\M2ePro\Model\Marketplace $marketplace
    ): array {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter(ProductTypeDictionaryResource::COLUMN_MARKETPLACE_ID, $marketplace->getId());

        return array_values($collection->getItems());
    }

    public function create(\Ess\M2ePro\Model\Walmart\Dictionary\ProductType $dictionaryProductType): void
    {
        $this->productTypeDictionaryResource->save($dictionaryProductType);
    }

    public function save(\Ess\M2ePro\Model\Walmart\Dictionary\ProductType $dictionaryProductType): void
    {
        $this->productTypeDictionaryResource->save($dictionaryProductType);
    }

    public function remove(\Ess\M2ePro\Model\Walmart\Dictionary\ProductType $dictionaryProductType): void
    {
        $this->productTypeDictionaryResource->save($dictionaryProductType);
    }

    // ----------------------------------------

    private function addToRuntimeCache(\Ess\M2ePro\Model\Walmart\Dictionary\ProductType $dictionaryProductType): void
    {
        $this->runtimeCache[(int)$dictionaryProductType->getId()] = $dictionaryProductType;
    }

    private function removeFromRuntimeCache(int $id): void
    {
        unset($this->runtimeCache[$id]);
    }

    private function tryGetFromRuntimeCache(int $id): ?\Ess\M2ePro\Model\Walmart\Dictionary\ProductType
    {
        return $this->runtimeCache[$id] ?? null;
    }
}

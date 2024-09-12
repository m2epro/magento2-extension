<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Amazon\Dictionary\ProductType;

use Ess\M2ePro\Model\ResourceModel\Amazon\Dictionary\ProductType as ProductTypeResource;

class Repository
{
    private ProductTypeResource $resource;
    private \Ess\M2ePro\Model\ResourceModel\Amazon\Dictionary\ProductType\CollectionFactory $collectionFactory;
    private \Ess\M2ePro\Model\Amazon\Dictionary\ProductTypeFactory $productTypeFactory;
    private \Ess\M2ePro\Model\ResourceModel\Marketplace\CollectionFactory $marketplaceCollectionFactory;

    private array $runtimeCache = [];

    public function __construct(
        ProductTypeResource $resource,
        \Ess\M2ePro\Model\ResourceModel\Amazon\Dictionary\ProductType\CollectionFactory $collectionFactory,
        \Ess\M2ePro\Model\Amazon\Dictionary\ProductTypeFactory $productTypeFactory,
        \Ess\M2ePro\Model\ResourceModel\Marketplace\CollectionFactory $marketplaceCollectionFactory
    ) {
        $this->resource = $resource;
        $this->collectionFactory = $collectionFactory;
        $this->productTypeFactory = $productTypeFactory;
        $this->marketplaceCollectionFactory = $marketplaceCollectionFactory;
    }

    public function create(\Ess\M2ePro\Model\Amazon\Dictionary\ProductType $productType): void
    {
        $this->resource->save($productType);
    }

    public function get(int $id): \Ess\M2ePro\Model\Amazon\Dictionary\ProductType
    {
        $productType = $this->find($id);
        if ($productType === null) {
            throw new \LogicException("Product Type $id not found.");
        }

        return $productType;
    }

    public function find(int $id): ?\Ess\M2ePro\Model\Amazon\Dictionary\ProductType
    {
        if (($model = $this->tryGetFromRuntimeCache($id)) !== null) {
            return $model;
        }

        $model = $this->productTypeFactory->createEmpty();
        $this->resource->load($model, $id);
        if ($model->isObjectNew()) {
            return null;
        }

        $this->addToRuntimeCache($model);

        return $model;
    }

    public function save(\Ess\M2ePro\Model\Amazon\Dictionary\ProductType $productType): void
    {
        $this->resource->save($productType);
    }

    public function remove(\Ess\M2ePro\Model\Amazon\Dictionary\ProductType $productType): void
    {
        $this->removeFromRuntimeCache((int)$productType->getId());

        $this->resource->delete($productType);
    }

    /**
     * @param \Ess\M2ePro\Model\Marketplace $marketplace
     *
     * @return \Ess\M2ePro\Model\Amazon\Dictionary\ProductType[]
     */
    public function findByMarketplace(
        \Ess\M2ePro\Model\Marketplace $marketplace
    ): array {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter(ProductTypeResource::COLUMN_MARKETPLACE_ID, ['eq' => $marketplace->getId()]);

        return array_values($collection->getItems());
    }

    /**
     * @param \Ess\M2ePro\Model\Marketplace $marketplace
     *
     * @return \Ess\M2ePro\Model\Amazon\Dictionary\ProductType[]
     */
    public function findValidByMarketplace(\Ess\M2ePro\Model\Marketplace $marketplace): array
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter(ProductTypeResource::COLUMN_MARKETPLACE_ID, ['eq' => $marketplace->getId()]);
        $collection->addFieldToFilter(ProductTypeResource::COLUMN_INVALID, ['eq' => 0]);

        return array_values($collection->getItems());
    }

    /**
     * @return \Ess\M2ePro\Model\Amazon\Dictionary\ProductType[]
     */
    public function findValidOutOfDate(): array
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter(ProductTypeResource::COLUMN_INVALID, ['eq' => 0]);
        $collection->addFieldToFilter(
            ProductTypeResource::COLUMN_SERVER_DETAILS_LAST_UPDATE_DATE,
            ['gt' => new \Zend_Db_Expr(ProductTypeResource::COLUMN_CLIENT_DETAILS_LAST_UPDATE_DATE)]
        );
        $collection->addFieldToFilter(ProductTypeResource::COLUMN_CLIENT_DETAILS_LAST_UPDATE_DATE, ['notnull' => true]);
        $collection->addFieldToFilter(ProductTypeResource::COLUMN_SERVER_DETAILS_LAST_UPDATE_DATE, ['notnull' => true]);

        return array_values($collection->getItems());
    }

    public function findByMarketplaceAndNick(
        int $marketplaceId,
        string $nick
    ): ?\Ess\M2ePro\Model\Amazon\Dictionary\ProductType {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter(ProductTypeResource::COLUMN_MARKETPLACE_ID, ['eq' => $marketplaceId])
                   ->addFieldToFilter(ProductTypeResource::COLUMN_NICK, ['eq' => $nick]);

        $result = $collection->getFirstItem();
        if ($result->isObjectNew()) {
            return null;
        }

        return $result;
    }

    // ----------------------------------------

    public function getValidNickMapByMarketplaceNativeId(): array
    {
        $marketplaceCollection = $this->marketplaceCollectionFactory->createWithAmazonChildMode();
        $marketplaceCollection->getSelect()
                              ->joinInner(
                                  ['dictionary' => $this->resource->getMainTable()],
                                  sprintf(
                                      'dictionary.%s = main_table.%s',
                                      ProductTypeResource::COLUMN_MARKETPLACE_ID,
                                      \Ess\M2ePro\Model\ResourceModel\Marketplace::COLUMN_ID
                                  ),
                                  []
                              );

        $marketplaceCollection->addFieldToFilter(
            sprintf('dictionary.%s', ProductTypeResource::COLUMN_INVALID),
            ['eq' => 0]
        );

        $marketplaceCollection->getSelect()->reset(\Magento\Framework\DB\Select::COLUMNS);
        $marketplaceCollection->getSelect()->columns(
            [
                'native_id' => sprintf('main_table.%s', \Ess\M2ePro\Model\ResourceModel\Marketplace::COLUMN_NATIVE_ID),
                'nick' => sprintf('dictionary.%s', ProductTypeResource::COLUMN_NICK),
            ]
        );

        $resultMap = [];
        foreach ($marketplaceCollection->toArray()['items'] ?? [] as $row) {
            $resultMap[(int)$row['native_id']][] = $row['nick'];
        }

        return $resultMap;
    }

    // ----------------------------------------

    private function addToRuntimeCache(\Ess\M2ePro\Model\Amazon\Dictionary\ProductType $productType): void
    {
        $this->runtimeCache[(int)$productType->getId()] = $productType;
    }

    private function removeFromRuntimeCache(int $id): void
    {
        unset($this->runtimeCache[$id]);
    }

    private function tryGetFromRuntimeCache(int $id): ?\Ess\M2ePro\Model\Amazon\Dictionary\ProductType
    {
        return $this->runtimeCache[$id] ?? null;
    }
}

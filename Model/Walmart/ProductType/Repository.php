<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Walmart\ProductType;

use Ess\M2ePro\Model\ResourceModel\Walmart\ProductType as ProductTypeResource;
use Ess\M2ePro\Model\ResourceModel\Walmart\Dictionary\ProductType as DictionaryProductTypeResource;
use Ess\M2ePro\Model\ResourceModel\Walmart\Listing as ListingResource;
use Ess\M2ePro\Model\ResourceModel\Walmart\Listing\Product as WalmartProductResource;
use Ess\M2ePro\Model\ResourceModel\Walmart\Listing\Auto\Category\Group\CollectionFactory
    as AutoCategoryGroupCollectionFactory;
use Ess\M2ePro\Model\ResourceModel\Walmart\Listing\Auto\Category\Group as AutoCategoryGroupResource;

class Repository
{
    private \Ess\M2ePro\Model\ResourceModel\Walmart\ProductType $productTypeResource;
    private \Ess\M2ePro\Model\ResourceModel\Walmart\ProductType\CollectionFactory $productTypeCollectionFactory;
    private \Ess\M2ePro\Model\ResourceModel\Walmart\Dictionary\ProductType $productTypeDictionaryResource;
    private \Ess\M2ePro\Model\Walmart\ProductTypeFactory $productTypeFactory;
    private \Ess\M2ePro\Model\ResourceModel\Walmart\Listing\CollectionFactory $listingCollectionFactory;
    private \Ess\M2ePro\Model\ResourceModel\Listing\Product\CollectionFactory $listingProductCollectionFactory;
    private AutoCategoryGroupCollectionFactory $autoCategoryGroupCollectionFactory;

    private array $runtimeCache = [];

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Walmart\ProductType $productTypeResource,
        \Ess\M2ePro\Model\Walmart\ProductTypeFactory $productTypeFactory,
        \Ess\M2ePro\Model\ResourceModel\Walmart\ProductType\CollectionFactory $productTypeCollectionFactory,
        \Ess\M2ePro\Model\ResourceModel\Walmart\Dictionary\ProductType $productTypeDictionaryResource,
        \Ess\M2ePro\Model\ResourceModel\Walmart\Listing\CollectionFactory $listingCollectionFactory,
        \Ess\M2ePro\Model\ResourceModel\Listing\Product\CollectionFactory $listingProductCollectionFactory,
        AutoCategoryGroupCollectionFactory $autoCategoryGroupCollectionFactory
    ) {
        $this->productTypeCollectionFactory = $productTypeCollectionFactory;
        $this->productTypeDictionaryResource = $productTypeDictionaryResource;
        $this->productTypeResource = $productTypeResource;
        $this->productTypeFactory = $productTypeFactory;
        $this->listingCollectionFactory = $listingCollectionFactory;
        $this->listingProductCollectionFactory = $listingProductCollectionFactory;
        $this->autoCategoryGroupCollectionFactory = $autoCategoryGroupCollectionFactory;
    }

    public function isExists(int $id): bool
    {
        return $this->find($id) !== null;
    }

    public function find(int $id): ?\Ess\M2ePro\Model\Walmart\ProductType
    {
        if (($model = $this->tryGetFromRuntimeCache($id)) !== null) {
            return $model;
        }

        $model = $this->productTypeFactory->createEmpty();
        $this->productTypeResource->load($model, $id);

        if ($model->isObjectNew()) {
            return null;
        }

        $this->addToRuntimeCache($model);

        return $model;
    }

    public function get(int $id): \Ess\M2ePro\Model\Walmart\ProductType
    {
        $model = $this->find($id);
        if ($model === null) {
            throw new \Ess\M2ePro\Model\Exception\EntityNotFound("Product Type $id not found.");
        }

        return $model;
    }

    public function delete(\Ess\M2ePro\Model\Walmart\ProductType $productType): void
    {
        $this->productTypeResource->delete($productType);
    }

    // ---------------------------------------

    public function isUsed(\Ess\M2ePro\Model\Walmart\ProductType $productType): bool
    {
        $productTypeId = (int)$productType->getId();

        return $this->isUsedInListings($productTypeId)
            || $this->isUsedInProducts($productTypeId)
            || $this->isUsedInAutoCategoryGroups($productTypeId);
    }

    private function isUsedInListings(int $productTypeId): bool
    {
        $listingCollection = $this->listingCollectionFactory->create();
        $listingCollection->getSelect()
                          ->where(
                              sprintf(
                                  '%s = ? OR %s = ?',
                                  ListingResource::COLUMN_AUTO_GLOBAL_ADDING_PRODUCT_TYPE_ID,
                                  ListingResource::COLUMN_AUTO_WEBSITE_ADDING_PRODUCT_TYPE_ID,
                              ),
                              $productTypeId
                          )
                          ->limit(1);

        /** @var \Ess\M2ePro\Model\Walmart\Listing $listing */
        $listing = $listingCollection->getFirstItem();

        return !$listing->isObjectNew();
    }

    private function isUsedInProducts(int $productTypeId): bool
    {
        $productCollection = $this->listingProductCollectionFactory->createWithWalmartChildMode();
        $productCollection->getSelect()
                          ->where(
                              sprintf('%s = ?', WalmartProductResource::COLUMN_PRODUCT_TYPE_ID),
                              $productTypeId
                          )
                          ->limit(1);

        /** @var \Ess\M2ePro\Model\Walmart\Listing\Product $product */
        $product = $productCollection->getFirstItem();

        return !$product->isObjectNew();
    }

    private function isUsedInAutoCategoryGroups(int $productTypeId): bool
    {
        $autoCategoryGroupCollection = $this->autoCategoryGroupCollectionFactory->create();
        $autoCategoryGroupCollection->getSelect()
                                    ->where(
                                        sprintf('%s = ?', AutoCategoryGroupResource::COLUMN_ADDING_PRODUCT_TYPE_ID),
                                        $productTypeId
                                    )
                                    ->limit(1);

        /** @var \Ess\M2ePro\Model\Walmart\Listing\Auto\Category\Group $autoCategoryGroup */
        $autoCategoryGroup = $autoCategoryGroupCollection->getFirstItem();

        return !$autoCategoryGroup->isObjectNew();
    }

    // ---------------------------------------

    /**
     * @return list<string, \Ess\M2ePro\Model\Walmart\ProductType>
     */
    public function retrieveListWithKeyNick(int $marketplaceId): array
    {
        $result = [];
        foreach ($this->retrieveByMarketplaceId($marketplaceId) as $item) {
            $result[$item->getDataByKey(DictionaryProductTypeResource::COLUMN_NICK)] = $item;
        }

        return $result;
    }

    /**
     * @return \Ess\M2ePro\Model\Walmart\ProductType[]
     */
    public function retrieveByMarketplaceId(int $marketplaceId): array
    {
        $collection = $this->productTypeCollectionFactory->create();
        $collection->join(
            ['ptd' => $this->productTypeDictionaryResource->getMainTable()],
            sprintf(
                'main_table.%s = ptd.%s',
                ProductTypeResource::COLUMN_DICTIONARY_PRODUCT_TYPE_ID,
                DictionaryProductTypeResource::COLUMN_ID
            ),
            DictionaryProductTypeResource::COLUMN_NICK
        );
        $collection->addFieldToFilter(DictionaryProductTypeResource::COLUMN_MARKETPLACE_ID, $marketplaceId);

        return array_values($collection->getItems());
    }

    public function findByTitleMarketplace(
        string $title,
        int $marketplaceId,
        ?int $productTypeId
    ): ?\Ess\M2ePro\Model\Walmart\ProductType {
        $collection = $this->productTypeCollectionFactory->create();
        $collection->joinInner(
            ['dictionary' => $this->productTypeDictionaryResource->getMainTable()],
            sprintf('dictionary.id = %s', ProductTypeResource::COLUMN_DICTIONARY_PRODUCT_TYPE_ID),
            [DictionaryProductTypeResource::COLUMN_MARKETPLACE_ID]
        );

        $collection->addFieldToFilter(sprintf('main_table.%s', ProductTypeResource::COLUMN_TITLE), ['eq' => $title]);
        $collection->addFieldToFilter('dictionary.marketplace_id', ['eq' => $marketplaceId]);
        if ($productTypeId !== null) {
            $collection->addFieldToFilter('main_table.id', ['neq' => $productTypeId]);
        }

        /** @var \Ess\M2ePro\Model\Walmart\ProductType $result */
        $result = $collection->getFirstItem();
        if ($result->isObjectNew()) {
            return null;
        }

        return $result;
    }

    public function findByDictionary(
        \Ess\M2ePro\Model\Walmart\Dictionary\ProductType $dictionaryProductType
    ): ?\Ess\M2ePro\Model\Walmart\ProductType {
        $collection = $this->productTypeCollectionFactory->create();
        $collection->addFieldToFilter(
            ProductTypeResource::COLUMN_DICTIONARY_PRODUCT_TYPE_ID,
            ['eq' => $dictionaryProductType->getId()]
        );

        /** @var \Ess\M2ePro\Model\Walmart\ProductType $result */
        $result = $collection->getFirstItem();
        if ($result->isObjectNew()) {
            return null;
        }

        return $result;
    }

    public function findByMarketplaceIdAndNick(
        int $marketplaceId,
        string $nick
    ): ?\Ess\M2ePro\Model\Walmart\ProductType {
        $collection = $this->productTypeCollectionFactory->create();
        $collection->joinInner(
            ['dictionary' => $this->productTypeDictionaryResource->getMainTable()],
            sprintf('dictionary.id = %s', ProductTypeResource::COLUMN_DICTIONARY_PRODUCT_TYPE_ID),
            ['marketplace_id' => 'marketplace_id']
        );

        $collection->addFieldToFilter(
            sprintf('dictionary.%s', DictionaryProductTypeResource::COLUMN_NICK),
            ['eq' => $nick]
        );
        $collection->addFieldToFilter(
            sprintf('dictionary.%s', DictionaryProductTypeResource::COLUMN_MARKETPLACE_ID),
            ['eq' => $marketplaceId]
        );

        /** @var \Ess\M2ePro\Model\Walmart\ProductType $productType */
        $productType = $collection->getFirstItem();
        if ($productType->isObjectNew()) {
            return null;
        }

        return $productType;
    }

    // ----------------------------------------

    private function addToRuntimeCache(\Ess\M2ePro\Model\Walmart\ProductType $productType): void
    {
        $this->runtimeCache[(int)$productType->getId()] = $productType;
    }

    private function removeFromRuntimeCache(int $id): void
    {
        unset($this->runtimeCache[$id]);
    }

    private function tryGetFromRuntimeCache(int $id): ?\Ess\M2ePro\Model\Walmart\ProductType
    {
        return $this->runtimeCache[$id] ?? null;
    }
}

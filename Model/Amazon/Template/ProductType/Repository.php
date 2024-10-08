<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Amazon\Template\ProductType;

use Ess\M2ePro\Model\ResourceModel\Amazon\Dictionary\ProductType as DictionaryProductTypeResource;
use Ess\M2ePro\Model\ResourceModel\Amazon\Listing\Product as AmazonProductResource;
use Ess\M2ePro\Model\ResourceModel\Amazon\Template\ProductType as ProductTypeResource;
use Ess\M2ePro\Model\ResourceModel\Amazon\Listing as AmazonListingResource;
use Ess\M2ePro\Model\ResourceModel\Amazon\Listing\Auto\Category\Group as AutoCategoryGroupResource;
use Ess\M2ePro\Model\ResourceModel\Amazon\Listing\Auto\Category\Group\CollectionFactory
    as AutoCategoryGroupCollectionFactory;

class Repository
{
    private ProductTypeResource $resource;
    private \Ess\M2ePro\Model\ResourceModel\Amazon\Template\ProductType\CollectionFactory $collectionFactory;
    private DictionaryProductTypeResource $dictionaryProductTypeResource;
    private \Ess\M2ePro\Model\Amazon\Template\ProductTypeFactory $productTypeFactory;
    private \Ess\M2ePro\Model\ResourceModel\Marketplace $marketplaceResource;
    private \Ess\M2ePro\Model\ResourceModel\Marketplace\CollectionFactory $marketplaceCollectionFactory;
    private \Ess\M2ePro\Model\ResourceModel\Listing\Product\CollectionFactory $listingProductCollectionFactory;
    private \Ess\M2ePro\Model\ResourceModel\Amazon\Listing\CollectionFactory $amazonListingCollectionFactory;
    private AutoCategoryGroupCollectionFactory $autoCategoryGroupCollectionFactory;

    private array $runtimeCache = [];

    public function __construct(
        ProductTypeResource $resource,
        \Ess\M2ePro\Model\ResourceModel\Amazon\Template\ProductType\CollectionFactory $collectionFactory,
        DictionaryProductTypeResource $dictionaryProductTypeResource,
        \Ess\M2ePro\Model\Amazon\Template\ProductTypeFactory $productTypeFactory,
        \Ess\M2ePro\Model\ResourceModel\Marketplace $marketplaceResource,
        \Ess\M2ePro\Model\ResourceModel\Marketplace\CollectionFactory $marketplaceCollectionFactory,
        \Ess\M2ePro\Model\ResourceModel\Listing\Product\CollectionFactory $listingProductCollectionFactory,
        \Ess\M2ePro\Model\ResourceModel\Amazon\Listing\CollectionFactory $amazonListingCollectionFactory,
        AutoCategoryGroupCollectionFactory $autoCategoryGroupCollectionFactory
    ) {
        $this->resource = $resource;
        $this->collectionFactory = $collectionFactory;
        $this->dictionaryProductTypeResource = $dictionaryProductTypeResource;
        $this->productTypeFactory = $productTypeFactory;
        $this->marketplaceResource = $marketplaceResource;
        $this->marketplaceCollectionFactory = $marketplaceCollectionFactory;
        $this->listingProductCollectionFactory = $listingProductCollectionFactory;
        $this->amazonListingCollectionFactory = $amazonListingCollectionFactory;
        $this->autoCategoryGroupCollectionFactory = $autoCategoryGroupCollectionFactory;
    }

    public function create(\Ess\M2ePro\Model\Amazon\Template\ProductType $productType): void
    {
        $this->resource->save($productType);
    }

    public function find(int $id): ?\Ess\M2ePro\Model\Amazon\Template\ProductType
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

    public function get(int $id): \Ess\M2ePro\Model\Amazon\Template\ProductType
    {
        $mode = $this->find($id);
        if ($mode === null) {
            throw new \Ess\M2ePro\Model\Exception\EntityNotFound("Product Type template $id not found.");
        }

        return $mode;
    }

    public function save(\Ess\M2ePro\Model\Amazon\Template\ProductType $productType): void
    {
        $this->resource->save($productType);
    }

    public function remove(\Ess\M2ePro\Model\Amazon\Template\ProductType $productType): void
    {
        $this->removeFromRuntimeCache((int)$productType->getId());

        $this->resource->delete($productType);
    }

    // ----------------------------------------

    public function findByTitleMarketplace(
        string $title,
        int $marketplaceId,
        ?int $productTypeId
    ): ?\Ess\M2ePro\Model\Amazon\Template\ProductType {
        $collection = $this->collectionFactory->create();
        $collection->joinInner(
            ['dictionary' => $this->dictionaryProductTypeResource->getMainTable()],
            sprintf('dictionary.id = %s', ProductTypeResource::COLUMN_DICTIONARY_PRODUCT_TYPE_ID),
            ['marketplace_id' => 'marketplace_id']
        );

        $collection->addFieldToFilter(sprintf('main_table.%s', ProductTypeResource::COLUMN_TITLE), ['eq' => $title]);
        $collection->addFieldToFilter('dictionary.marketplace_id', ['eq' => $marketplaceId]);
        if ($productTypeId !== null) {
            $collection->addFieldToFilter('main_table.id', ['neq' => $productTypeId]);
        }

        $result = $collection->getFirstItem();
        if ($result->isObjectNew()) {
            return null;
        }

        return $result;
    }

    public function findByMarketplaceIdAndNick(
        int $marketplaceId,
        string $nick
    ): ?\Ess\M2ePro\Model\Amazon\Template\ProductType {
        $collection = $this->collectionFactory->create();
        $collection->joinInner(
            ['dictionary' => $this->dictionaryProductTypeResource->getMainTable()],
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

        $result = $collection->getFirstItem();
        if ($result->isObjectNew()) {
            return null;
        }

        return $result;
    }

    /**
     * @param int $marketplaceId
     *
     * @return \Ess\M2ePro\Model\Amazon\Template\ProductType[]
     */
    public function findByMarketplaceId(int $marketplaceId): array
    {
        $collection = $this->collectionFactory->create();
        $collection->joinInner(
            ['dictionary' => $this->dictionaryProductTypeResource->getMainTable()],
            sprintf(
                'main_table.%s = dictionary.%s',
                ProductTypeResource::COLUMN_DICTIONARY_PRODUCT_TYPE_ID,
                DictionaryProductTypeResource::COLUMN_ID,
            ),
            [],
        );
        $collection->addFieldToFilter(
            sprintf(
                'dictionary.%s',
                DictionaryProductTypeResource::COLUMN_MARKETPLACE_ID,
            ),
            ['eq' => $marketplaceId]
        );

        return array_values($collection->getItems());
    }

    /**
     * @param \Ess\M2ePro\Model\Amazon\Dictionary\ProductType $dictionaryProductType
     *
     * @return \Ess\M2ePro\Model\Amazon\Template\ProductType[]
     */
    public function findByDictionary(\Ess\M2ePro\Model\Amazon\Dictionary\ProductType $dictionaryProductType): array
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter(
            ProductTypeResource::COLUMN_DICTIONARY_PRODUCT_TYPE_ID,
            ['eq' => $dictionaryProductType->getId()]
        );

        return array_values($collection->getItems());
    }

    // ----------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Marketplace[]
     */
    public function getUsingMarketplaces(): array
    {
        $marketplaceCollection = $this->marketplaceCollectionFactory->createWithAmazonChildMode();
        $marketplaceCollection->getSelect()
                              ->joinInner(
                                  ['dictionary' => $this->dictionaryProductTypeResource->getMainTable()],
                                  sprintf(
                                      'dictionary.%s = main_table.%s',
                                      DictionaryProductTypeResource::COLUMN_MARKETPLACE_ID,
                                      \Ess\M2ePro\Model\ResourceModel\Marketplace::COLUMN_ID
                                  ),
                                  []
                              );
        $marketplaceCollection->getSelect()
                              ->joinInner(
                                  ['template' => $this->resource->getMainTable()],
                                  sprintf(
                                      'template.%s = dictionary.%s',
                                      ProductTypeResource::COLUMN_DICTIONARY_PRODUCT_TYPE_ID,
                                      DictionaryProductTypeResource::COLUMN_ID
                                  ),
                                  []
                              );

        $marketplaceCollection->getSelect()
                              ->group(sprintf('main_table.%s', \Ess\M2ePro\Model\ResourceModel\Marketplace::COLUMN_ID));

        $marketplaceCollection->setOrder(
            sprintf('main_table.%s', \Ess\M2ePro\Model\ResourceModel\Marketplace::COLUMN_SORDER),
            'ASC'
        );

        return array_values($marketplaceCollection->getItems());
    }

    // ----------------------------------------

    public function isUsed(\Ess\M2ePro\Model\Amazon\Template\ProductType $productType): bool
    {
        $productTypeId = (int)$productType->getId();

        return $this->isUsedInProducts($productTypeId)
            || $this->isUsedInListings($productTypeId)
            || $this->isUsedInAutoCategoryGroups($productTypeId);
    }

    private function isUsedInProducts(int $productTypeId): bool
    {
        $collection = $this->listingProductCollectionFactory->createWithAmazonChildMode();

        $collection->getSelect()
                   ->where(
                       sprintf('%s = ?', AmazonProductResource::COLUMN_TEMPLATE_PRODUCT_TYPE_ID),
                       $productTypeId
                   )
                   ->limit(1);

        $product = $collection->getFirstItem();

        return !$product->isObjectNew();
    }

    private function isUsedInListings(int $productTypeId): bool
    {
        $listingCollection = $this->amazonListingCollectionFactory->create();
        $listingCollection->getSelect()
                          ->where(
                              sprintf(
                                  '%s = ? OR %s = ?',
                                  AmazonListingResource::COLUMN_AUTO_GLOBAL_ADDING_PRODUCT_TYPE_TEMPLATE_ID,
                                  AmazonListingResource::COLUMN_AUTO_WEBSITE_ADDING_PRODUCT_TYPE_TEMPLATE_ID,
                              ),
                              $productTypeId
                          )
                          ->limit(1);

        /** @var \Ess\M2ePro\Model\Amazon\Listing $listing */
        $listing = $listingCollection->getFirstItem();

        return !$listing->isObjectNew();
    }

    private function isUsedInAutoCategoryGroups(int $productTypeId): bool
    {
        $autoCategoryGroupCollection = $this->autoCategoryGroupCollectionFactory->create();
        $autoCategoryGroupCollection->getSelect()
                                    ->where(
                                        sprintf(
                                            '%s = ?',
                                            AutoCategoryGroupResource::COLUMN_ADDING_PRODUCT_TYPE_TEMPLATE_ID
                                        ),
                                        $productTypeId
                                    )
                                    ->limit(1);

        /** @var \Ess\M2ePro\Model\Amazon\Listing\Auto\Category\Group $autoCategoryGroup */
        $autoCategoryGroup = $autoCategoryGroupCollection->getFirstItem();

        return !$autoCategoryGroup->isObjectNew();
    }

    // ----------------------------------------

    public function getCollectionForGrid(): ProductTypeResource\Collection
    {
        $collection = $this->collectionFactory->create();
        $collection->getSelect()->join(
            ['adpt' => $this->dictionaryProductTypeResource->getMainTable()],
            sprintf(
                'adpt.%s = main_table.%s',
                DictionaryProductTypeResource::COLUMN_ID,
                ProductTypeResource::COLUMN_DICTIONARY_PRODUCT_TYPE_ID
            ),
            ['product_type_title' => sprintf('adpt.%s', DictionaryProductTypeResource::COLUMN_TITLE)]
        );

        $collection->getSelect()->join(
            ['m' => $this->marketplaceResource->getMainTable()],
            sprintf('m.id = adpt.%s', DictionaryProductTypeResource::COLUMN_MARKETPLACE_ID),
            ['marketplace_title' => sprintf('m.%s', \Ess\M2ePro\Model\ResourceModel\Marketplace::COLUMN_TITLE)]
        );

        return $collection;
    }

    // ----------------------------------------

    private function addToRuntimeCache(\Ess\M2ePro\Model\Amazon\Template\ProductType $productType): void
    {
        $this->runtimeCache[(int)$productType->getId()] = $productType;
    }

    private function removeFromRuntimeCache(int $id): void
    {
        unset($this->runtimeCache[$id]);
    }

    private function tryGetFromRuntimeCache(int $id): ?\Ess\M2ePro\Model\Amazon\Template\ProductType
    {
        return $this->runtimeCache[$id] ?? null;
    }
}

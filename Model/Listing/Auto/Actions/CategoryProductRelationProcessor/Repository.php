<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Listing\Auto\Actions\CategoryProductRelationProcessor;

class Repository
{
    /** @var \Magento\Catalog\Model\ResourceModel\Product\Website */
    private $productWebsiteResource;
    /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Auto\Category\CollectionFactory */
    private $autoCategoryCollectionFactory;
    /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Auto\Category\Group */
    private $categoryGroupResource;
    /** @var \Ess\M2ePro\Model\ResourceModel\Listing */
    private $listingResource;
    /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product */
    private $listingProductResource;
    /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\CollectionFactory */
    private $listingCollectionFactory;
    /** @var \Ess\M2ePro\Model\ResourceModel\Magento\Product\CollectionFactory */
    private $magentoProductCollectionFactory;

    public function __construct(
        \Magento\Catalog\Model\ResourceModel\Product\Website $productWebsiteResource,
        \Ess\M2ePro\Model\ResourceModel\Listing\Auto\Category\CollectionFactory $autoCategoryCollectionFactory,
        \Ess\M2ePro\Model\ResourceModel\Listing\Auto\Category\Group $categoryGroupResource,
        \Ess\M2ePro\Model\ResourceModel\Listing $listingResource,
        \Ess\M2ePro\Model\ResourceModel\Listing\Product $listingProductResource,
        \Ess\M2ePro\Model\ResourceModel\Listing\Product\CollectionFactory $listingCollectionFactory,
        \Ess\M2ePro\Model\ResourceModel\Magento\Product\CollectionFactory $magentoProductCollectionFactory
    ) {
        $this->productWebsiteResource = $productWebsiteResource;
        $this->autoCategoryCollectionFactory = $autoCategoryCollectionFactory;
        $this->categoryGroupResource = $categoryGroupResource;
        $this->listingResource = $listingResource;
        $this->listingProductResource = $listingProductResource;
        $this->listingCollectionFactory = $listingCollectionFactory;
        $this->magentoProductCollectionFactory = $magentoProductCollectionFactory;
    }

    public function isExistsAutoActionsForCategory(int $categoryId): bool
    {
        $collection = $this->autoCategoryCollectionFactory->create();
        $collection->addFieldToFilter(
            \Ess\M2ePro\Model\ResourceModel\Listing\Auto\Category::CATEGORY_ID_FIELD,
            $categoryId
        );

        return (bool)$collection->getSize();
    }

    /**
     * @return array<int, int[]>
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function retrieveCatalogWebsiteProductsIds(array $productIds): array
    {
        $select = $this->productWebsiteResource->getConnection()->select()->from(
            $this->productWebsiteResource->getMainTable(),
            ['website_id', 'product_id']
        )->where(
            'product_id IN (?)',
            $productIds
        );

        $rowSet = $this->productWebsiteResource->getConnection()->fetchAll($select);

        $result = [];
        foreach ($rowSet as $row) {
            $result[(int)$row['website_id']][] = (int)$row['product_id'];
        }

        return $result;
    }

    /**
     * @return int[]
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function findListingIdsWithEnabledRuleAddedToCategory(int $categoryId, array $storeIds): array
    {
        return $this->getListingIdsWithEnabledCategoryRules(
            'adding_mode',
            $categoryId,
            $storeIds
        );
    }

    /**
     * @return int[]
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function findListingIdsWithEnabledRuleRemovedFromCategory(int $categoryId, array $storeIds): array
    {
        return $this->getListingIdsWithEnabledCategoryRules(
            'deleting_mode',
            $categoryId,
            $storeIds
        );
    }

    /**
     * @return int[]
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getListingIdsWithEnabledCategoryRules(string $mode, int $categoryId, array $storeIds): array
    {
        $collection = $this->autoCategoryCollectionFactory->create();
        $collection
            ->getSelect()
            ->distinct();
        $collection->join(
            ['category_group' => $this->categoryGroupResource->getMainTable()],
            'main_table.group_id = category_group.id'
        );
        $collection->getSelect()->reset(\Magento\Framework\DB\Select::COLUMNS);
        $collection->join(
            ['listing' => $this->listingResource->getMainTable()],
            'category_group.listing_id = listing.id',
            ['listing_id' => 'id']
        );
        $collection->addFieldToFilter('category_id', $categoryId);
        $collection->addFieldToFilter('store_id', ['in' => $storeIds]);
        $collection->addFieldToFilter($mode, ['neq' => 0]);

        return array_map(function (\Magento\Framework\DataObject $dataObject) {
            return (int)$dataObject->getDataByKey('listing_id');
        }, $collection->getItems());
    }

    /**
     * @return \Magento\Catalog\Model\Product[]
     */
    public function findProductsThatNotInListings(array $productIds, array $listingIds): array
    {
        $productCollection = $this->magentoProductCollectionFactory->create();
        $productCollection->addFieldToFilter('entity_id', ['in' => $productIds]);

        $listingProductCollection = $this->listingCollectionFactory->create();
        $listingProductSelect = $listingProductCollection->getSelect();
        $listingProductSelect->where('main_table.product_id = e.entity_id');
        $listingProductSelect->where('main_table.listing_id IN (?)', $listingIds);

        $productCollection->getSelect()->where('NOT EXISTS (?)', $listingProductSelect);

        return $productCollection->getItems();
    }

    /**
     * @return \Magento\Catalog\Model\Product[]
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function findProductsThatInListings($productIds, array $listingIds): array
    {
        $productCollection = $this->magentoProductCollectionFactory->create();
        $select = $productCollection->getSelect();
        $select->join(
            ['lp' => $this->listingProductResource->getMainTable()],
            'lp.product_id = e.entity_id'
        );
        $select
            ->reset(\Magento\Framework\DB\Select::COLUMNS)
            ->columns('e.*');
        $select->where('lp.product_id IN (?)', $productIds);
        $select->where('lp.listing_id IN (?)', $listingIds);

        return $productCollection->getItems();
    }
}

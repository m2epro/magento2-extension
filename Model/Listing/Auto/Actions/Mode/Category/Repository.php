<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Listing\Auto\Actions\Mode\Category;

class Repository
{
    /** @var \Ess\M2ePro\Model\Listing\Auto\Actions\Mode\Category\GroupSetFactory */
    private $groupSetFactory;
    /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Auto\Category\CollectionFactory */
    private $autoCategoryCollectionFactory;
    /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Auto\Category\Group\CollectionFactory */
    private $autoCategoryGroupCollectionFactory;
    /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Auto\Category\Group */
    private $autoCategoryGroupResource;
    /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection\Factory */
    private $listingProductCollectionFactory;
    /** @var \Ess\M2ePro\Model\ActiveRecord\Factory */
    private $activeRecordFactory;
    /** @var \Magento\Store\Model\StoreManagerInterface */
    private $storeManager;

    /** @var \Ess\M2ePro\Model\Listing[] */
    private $cacheListing = [];
    /** @var \Ess\M2ePro\Model\Listing\Auto\Category\Group[][] */
    private $cacheAutoCategoryGroups = [];
    /** @var int[] */
    private $cacheStoreWebsiteId = [];

    public function __construct(
        \Ess\M2ePro\Model\Listing\Auto\Actions\Mode\Category\GroupSetFactory $groupSetFactory,
        \Ess\M2ePro\Model\ResourceModel\Listing\Auto\Category\CollectionFactory $autoCategoryCollectionFactory,
        \Ess\M2ePro\Model\ResourceModel\Listing\Auto\Category\Group $autoCategoryGroupResource,
        \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection\Factory $listingProductCollectionFactory,
        \Ess\M2ePro\Model\ResourceModel\Listing\Auto\Category\Group\CollectionFactory $autoCategoryGroupCollFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->groupSetFactory = $groupSetFactory;
        $this->autoCategoryCollectionFactory = $autoCategoryCollectionFactory;
        $this->autoCategoryGroupCollectionFactory = $autoCategoryGroupCollFactory;
        $this->autoCategoryGroupResource = $autoCategoryGroupResource;
        $this->listingProductCollectionFactory = $listingProductCollectionFactory;
        $this->activeRecordFactory = $activeRecordFactory;
        $this->storeManager = $storeManager;
    }

    /**
     * @param int[] $categoryIds
     * @param \Magento\Catalog\Model\Product|null $magentoProduct
     *
     * @return GroupSet
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getGroupSet(
        array $categoryIds,
        \Magento\Catalog\Model\Product $magentoProduct = null
    ): GroupSet {
        $autoCategoryCollection = $this->autoCategoryCollectionFactory->create();
        $autoCategoryCollection->selectCategoryId();
        $autoCategoryCollection->selectGroupId();

        $autoCategoryCollection->join(
            ['acg' => $this->autoCategoryGroupResource->getMainTable()],
            'group_id=acg.id',
            ['listing_id']
        );

        $autoCategorySubCollection = $this->autoCategoryCollectionFactory->create();
        $autoCategorySubCollection->selectGroupId();
        $autoCategorySubCollection->whereCategoryIdIn($categoryIds);

        $autoCategoryCollection->getSelect()->where(
            'main_table.group_id IN (?)',
            $autoCategorySubCollection->getSelect()
        );

        if ($magentoProduct) {
            $listingProductCollect = $this->listingProductCollectionFactory->create();
            $listingProductCollect->selectListingId();
            $listingProductCollect->whereProductIdEq($magentoProduct->getId());

            $autoCategoryCollection->getSelect()->where(
                'acg.listing_id IN (?)',
                $listingProductCollect->getSelect()
            );
        }

        return $this->makeFilledGroupSet($autoCategoryCollection->toArray()['items']);
    }

    /**
     * @param array $items
     *
     * @return GroupSet
     */
    private function makeFilledGroupSet(array $items): GroupSet
    {
        $groups = [];
        foreach ($items as $item) {
            $groups[$item['listing_id']]['category_ids'][] = $item['category_id'];
            $groups[$item['listing_id']]['group_ids'][] = $item['group_id'];
        }

        $groupSet = $this->groupSetFactory->create();
        foreach ($groups as $listingId => $val) {
            $groupSet->fillGroupData($listingId, $val['category_ids'], $val['group_ids']);
        }

        return $groupSet;
    }

    /**
     * @param int $listingId
     *
     * @return int
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getStoreWebsiteIdByListingId(int $listingId): int
    {
        if (isset($this->cacheStoreWebsiteId[$listingId])) {
            return $this->cacheStoreWebsiteId[$listingId];
        }

        $listing = $this->getLoadedListing($listingId);

        /** @var \Magento\Store\Model\Store $store */
        $store = $this->storeManager->getStore($listing->getStoreId());
        $id = $store->getWebsite()->getId();

        return $this->cacheStoreWebsiteId[$listingId] = $id;
    }

    /**
     * @param int $listingId
     *
     * @return \Ess\M2ePro\Model\Listing
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getLoadedListing(int $listingId): \Ess\M2ePro\Model\Listing
    {
        if (isset($this->cacheListing[$listingId])) {
            return $this->cacheListing[$listingId];
        }

        /** @var \Ess\M2ePro\Model\Listing $listing */
        $listing = $this->activeRecordFactory->getCachedObjectLoaded('Listing', $listingId);

        return $this->cacheListing[$listingId] = $listing;
    }

    /**
     * @param \Ess\M2ePro\Model\Listing\Auto\Actions\Mode\Category\Group $group
     *
     * @return \Ess\M2ePro\Model\Listing\Auto\Category\Group[]
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getAutoCategoryGroups(Group $group): array
    {
        $listingId = $group->getListingId();
        if (isset($this->cacheAutoCategoryGroups[$listingId])) {
            return $this->cacheAutoCategoryGroups[$listingId];
        }

        $autoCategoryGroupIds = $group->getAutoCategoryGroupIds();

        if (count($autoCategoryGroupIds) === 0) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Not allowed value');
        }

        $collection = $this->autoCategoryGroupCollectionFactory->create();
        $collection->getSelect()->where('id IN (?)', $autoCategoryGroupIds);

        $collection->whereAddingOrDeletingModeEnabled();
        $items = $collection->getItems();

        return $this->cacheAutoCategoryGroups[$listingId] = $items;
    }
}

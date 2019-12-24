<?php
/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\MSI;

use Magento\InventoryIndexer\Indexer\Source\GetAssignedStockIds;
use Magento\InventorySalesApi\Model\GetAssignedSalesChannelsForStockInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;

/**
 * Class \Ess\M2ePro\Model\MSI\AffectedProducts
 */
class AffectedProducts extends \Ess\M2ePro\Model\AbstractModel
{
    /** @var \Magento\Store\Api\WebsiteRepositoryInterface */
    protected $websiteRepository;

    /** @var \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory */
    protected $activeRecordFactory;

    /** @var \Magento\Catalog\Model\ResourceModel\Product */
    protected $productResource;

    // ---------------------------------------

    /** @var \Magento\InventoryIndexer\Indexer\Source\GetAssignedStockIds */
    protected $getAssignedStockIds;

    /** @var \Magento\InventorySalesApi\Model\GetAssignedSalesChannelsForStockInterface */
    protected $getAssignedChannels;

    //########################################

    /*
    * Dependencies can not be specified in constructor because MSI modules can be not installed.
    */
    public function __construct(
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Store\Api\WebsiteRepositoryInterface $websiteRepository,
        \Magento\Catalog\Model\ResourceModel\Product $productResource,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        array $data = []
    ) {
        parent::__construct($helperFactory, $modelFactory, $data);

        $this->websiteRepository = $websiteRepository;
        $this->activeRecordFactory = $activeRecordFactory;
        $this->productResource = $productResource;

        $this->getAssignedStockIds = $objectManager->get(GetAssignedStockIds::class);
        $this->getAssignedChannels = $objectManager->get(GetAssignedSalesChannelsForStockInterface::class);
    }

    //########################################

    /**
     * @param $sourceCode
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getAffectedStoresBySource($sourceCode)
    {
        $cacheKey   = __METHOD__.$sourceCode;
        $cacheValue = $this->getHelper('Data_Cache_Runtime')->getValue($cacheKey);

        if ($cacheValue !== null) {
            return $cacheValue;
        }

        $storesIds = [];
        foreach ($this->getAssignedStockIds->execute([$sourceCode]) as $stockId) {
            foreach ($this->getAffectedStoresByStock($stockId) as $storeId) {
                $storesIds[$storeId] = $storeId;
            }
        }
        $storesIds = array_values($storesIds);

        $this->getHelper('Data_Cache_Runtime')->setValue($cacheKey, $storesIds);
        return $storesIds;
    }

    /**
     * @param $stockId
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getAffectedStoresByStock($stockId)
    {
        $cacheKey   = __METHOD__.$stockId;
        $cacheValue = $this->getHelper('Data_Cache_Runtime')->getValue($cacheKey);

        if ($cacheValue !== null) {
            return $cacheValue;
        }

        $storesIds = [];
        foreach ($this->getAssignedChannels->execute($stockId) as $channel) {

            if ($channel->getType() !== SalesChannelInterface::TYPE_WEBSITE) {
                continue;
            }

            foreach ($this->getAffectedStoresByChannel($channel->getCode()) as $storeId) {
                $storesIds[$storeId] = $storeId;
            }
        }
        $storesIds = array_values($storesIds);

        $this->getHelper('Data_Cache_Runtime')->setValue($cacheKey, $storesIds);
        return $storesIds;
    }

    /**
     * @param $channelCode
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getAffectedStoresByChannel($channelCode)
    {
        $cacheKey   = __METHOD__.$channelCode;
        $cacheValue = $this->getHelper('Data_Cache_Runtime')->getValue($cacheKey);

        if ($cacheValue !== null) {
            return $cacheValue;
        }

        $storesIds = [];
        try {
            /** @var \Magento\Store\Model\Website $website */
            $website = $this->websiteRepository->get($channelCode);

            foreach ($website->getStoreIds() as $storeId) {
                $storesIds[$storeId] = (int)$storeId;
            }

            if ($website->getIsDefault()) {
                $storesIds[] = \Magento\Store\Model\Store::DEFAULT_STORE_ID;
            }

        } catch (\Magento\Framework\Exception\NoSuchEntityException $noSuchEntityException) {
            return [];
        }
        $storesIds = array_values($storesIds);

        $this->getHelper('Data_Cache_Runtime')->setValue($cacheKey, $storesIds);
        return $storesIds;
    }

    //########################################

    /**
     * @param $sourceCode
     * @return \Ess\M2ePro\Model\Listing[]
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getAffectedListingsBySource($sourceCode)
    {
        $cacheKey   = __METHOD__.$sourceCode;
        $cacheValue = $this->getHelper('Data_Cache_Runtime')->getValue($cacheKey);

        if ($cacheValue !== null) {
            return $cacheValue;
        }

        $storesIds = $this->getAffectedStoresBySource($sourceCode);
        if (empty($storesIds)) {
            return [];
        }

        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Collection $collection */
        $collection = $this->activeRecordFactory->getObject('Listing')->getCollection();
        $collection->addFieldToFilter('store_id', ['in' => $storesIds]);

        $this->getHelper('Data_Cache_Runtime')->setValue($cacheKey, $collection->getItems());
        return $collection->getItems();
    }

    /**
     * @param $stockId
     * @return \Ess\M2ePro\Model\Listing[]
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getAffectedListingsByStock($stockId)
    {
        $cacheKey   = __METHOD__.$stockId;
        $cacheValue = $this->getHelper('Data_Cache_Runtime')->getValue($cacheKey);

        if ($cacheValue !== null) {
            return $cacheValue;
        }

        $storesIds = $this->getAffectedStoresByStock($stockId);
        if (empty($storesIds)) {
            return [];
        }

        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Collection $collection */
        $collection = $this->activeRecordFactory->getObject('Listing')->getCollection();
        $collection->addFieldToFilter('store_id', ['in' => $storesIds]);

        $this->getHelper('Data_Cache_Runtime')->setValue($cacheKey, $collection->getItems());
        return $collection->getItems();
    }

    /**
     * @param $channelCode
     * @return \Ess\M2ePro\Model\Listing[]
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getAffectedListingsByChannel($channelCode)
    {
        $cacheKey   = __METHOD__.$channelCode;
        $cacheValue = $this->getHelper('Data_Cache_Runtime')->getValue($cacheKey);

        if ($cacheValue !== null) {
            return $cacheValue;
        }

        $storesIds = $this->getAffectedStoresByChannel($channelCode);
        if (empty($storesIds)) {
            return [];
        }

        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Collection $collection */
        $collection = $this->activeRecordFactory->getObject('Listing')->getCollection();
        $collection->addFieldToFilter('store_id', ['in' => $storesIds]);

        $this->getHelper('Data_Cache_Runtime')->setValue($cacheKey, $collection->getItems());
        return $collection->getItems();
    }

    //########################################

    /**
     * @param $sourceCode
     * @param $sku
     * @return \Ess\M2ePro\Model\Listing\Product[]
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getAffectedProductsBySourceAndSku($sourceCode, $sku)
    {
        $storesIds = $this->getAffectedStoresBySource($sourceCode);
        if (empty($storesIds)) {
            return [];
        }

        return $this->activeRecordFactory->getObject('Listing\Product')->getResource()
            ->getItemsByProductId(
                $this->productResource->getIdBySku($sku),
                ['store_id' => $storesIds]
            );
    }

    /**
     * @param $stockId
     * @param $sku
     * @return \Ess\M2ePro\Model\Listing\Product[]
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getAffectedProductsByStockAndSku($stockId, $sku)
    {
        $storesIds = $this->getAffectedStoresByStock($stockId);
        if (empty($storesIds)) {
            return [];
        }

        return $this->activeRecordFactory->getObject('Listing\Product')->getResource()
            ->getItemsByProductId(
                $this->productResource->getIdBySku($sku),
                ['store_id' => $storesIds]
            );
    }

    //########################################
}
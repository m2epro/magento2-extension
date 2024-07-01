<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\AmazonMcf\Amazon\Provider\Product;

use Ess\M2ePro\Model\ResourceModel\Amazon\Listing\Product as AmazonListingProductResource;
use Ess\M2ePro\Model\ResourceModel\Listing\Product as ListingProductResource;
use Ess\M2ePro\Model\ResourceModel\Listing as ListingResource;

class Repository
{
    /** @var \Ess\M2ePro\Model\ResourceModel\MSI\Magento\SourceItem\CollectionFactory */
    private $sourceItemCollectionFactory;
    /** @var \Magento\Catalog\Model\ResourceModel\Product */
    private $magentoProductResource;
    /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product */
    private $listingProductResource;
    /** @var \Ess\M2ePro\Model\ResourceModel\Amazon\Listing\Product */
    private $amazonListingProductResource;
    /** @var \Ess\M2ePro\Model\Amazon\Account\Repository */
    private $amazonAccountRepository;
    /** @var \Ess\M2ePro\Model\ResourceModel\Listing */
    private $listingResource;

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\MSI\Magento\SourceItem\CollectionFactory $sourceItemCollectionFactory,
        \Magento\Catalog\Model\ResourceModel\Product $magentoProductResource,
        \Ess\M2ePro\Model\ResourceModel\Listing\Product $listingProductResource,
        \Ess\M2ePro\Model\ResourceModel\Listing $listingResource,
        \Ess\M2ePro\Model\ResourceModel\Amazon\Listing\Product $amazonListingProductResource,
        \Ess\M2ePro\Model\Amazon\Account\Repository $amazonAccountRepository
    ) {
        $this->sourceItemCollectionFactory = $sourceItemCollectionFactory;
        $this->magentoProductResource = $magentoProductResource;
        $this->listingResource = $listingResource;
        $this->listingProductResource = $listingProductResource;
        $this->amazonListingProductResource = $amazonListingProductResource;
        $this->amazonAccountRepository = $amazonAccountRepository;
    }

    /**
     * @return \M2E\AmazonMcf\Model\Provider\Amazon\Product\ItemCollection
     */
    public function getItemCollection(string $merchantId)
    {
        $itemCollection = new \M2E\AmazonMcf\Model\Provider\Amazon\Product\ItemCollection();
        if (empty($this->amazonAccountRepository->isExistsWithMerchantId($merchantId))) {
            return $itemCollection;
        }

        $merchantSetting = $this->amazonAccountRepository
            ->getFistByMerchantId($merchantId)
            ->getMerchantSetting();

        if (!$merchantSetting->isManageFbaInventory()) {
            return $itemCollection;
        }

        $items = $this->retrieveItems(
            $merchantSetting->getManageFbaInventorySourceName(),
            $this->amazonAccountRepository->retrieveEntityIdsByMerchantId($merchantId)
        );

        foreach ($items as $item) {
            $asin = !empty($item->getDataByKey('asin')) ? $item->getDataByKey('asin') : null;
            $itemCollection->add(
                $this->createItem(
                    $merchantId,
                    $item->getDataByKey('channel_sku'),
                    (int)$item->getDataByKey('magento_product_entity_id'),
                    $item->getDataByKey('magento_product_sku'),
                    (int)$item->getDataByKey('online_afn_qty'),
                    $asin
                )
            );
        }

        return $itemCollection;
    }

    /**
     * @return \Magento\Framework\DataObject[]
     */
    private function retrieveItems(string $sourceCode, array $accountsIds): array
    {
        $collection = $this->sourceItemCollectionFactory->create();
        $collection->join(
            ['mp' => $this->magentoProductResource->getEntityTable()],
            'main_table.sku = mp.sku',
            ['magento_product_sku' => 'main_table.sku']
        );
        $collection->join(
            ['lp' => $this->listingProductResource->getMainTable()],
            sprintf('mp.entity_id = lp.%s', ListingProductResource::PRODUCT_ID_FIELD),
            ['magento_product_entity_id' => 'mp.entity_id']
        );
        $collection->join(
            ['l' => $this->listingResource->getMainTable()],
            sprintf(
                'lp.%s = l.%s',
                ListingProductResource::LISTING_ID_FIELD,
                ListingResource::COLUMN_ID
            )
        );
        $collection->join(
            ['alp' => $this->amazonListingProductResource->getMainTable()],
            sprintf(
                'lp.%s = alp.%s',
                ListingProductResource::COLUMN_ID,
                AmazonListingProductResource::COLUMN_LISTING_PRODUCT_ID
            ),
            [
                'channel_sku' => sprintf('alp.%s', AmazonListingProductResource::COLUMN_SKU),
                'online_afn_qty' => sprintf('alp.%s', AmazonListingProductResource::COLUMN_ONLINE_AFN_QTY),
                'asin' => sprintf('alp.%s', AmazonListingProductResource::COLUMN_GENERAL_ID)
            ]
        );

        $collection->addFieldToFilter(sprintf('l.%s', ListingResource::COLUMN_ACCOUNT_ID), ['in' => $accountsIds]);
        $collection->addFieldToFilter('main_table.source_code', $sourceCode);
        $collection->addFieldToFilter(
            sprintf('lp.%s', ListingProductResource::STATUS_FIELD),
            \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED
        );
        $collection->addFieldToFilter(
            sprintf('alp.%s', AmazonListingProductResource::COLUMN_IS_AFN_CHANNEL),
            1
        );
        $collection->getSelect()->where(
            sprintf(
                'alp.%s IS NOT NULL',
                AmazonListingProductResource::COLUMN_SKU
            )
        );

        return $collection->getItems();
    }

    /**
     * @return \M2E\AmazonMcf\Model\Provider\Amazon\Product\Item
     */
    private function createItem(
        string $merchantId,
        string $channelSku,
        int $magentoProductId,
        string $magentoProductSku,
        int $qty,
        ?string $asin
    ) {
        $item = new \M2E\AmazonMcf\Model\Provider\Amazon\Product\Item(
            $merchantId,
            $channelSku,
            $magentoProductId,
            $magentoProductSku,
            $qty
        );

        if ($asin !== null && method_exists($item, 'setAsin')) {
            $item->setAsin($asin);
        }

        return $item;
    }
}

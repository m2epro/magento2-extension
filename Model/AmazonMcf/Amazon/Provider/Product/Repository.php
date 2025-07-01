<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\AmazonMcf\Amazon\Provider\Product;

use Ess\M2ePro\Model\ResourceModel\Amazon\Listing\Product as AmazonListingProductResource;
use Ess\M2ePro\Model\ResourceModel\Listing\Product as ListingProductResource;
use Ess\M2ePro\Model\ResourceModel\Listing as ListingResource;

class Repository
{
    private \Ess\M2ePro\Model\ResourceModel\MSI\Magento\SourceItem\CollectionFactory $sourceItemCollectionFactory;
    private \Magento\Catalog\Model\ResourceModel\Product $magentoProductResource;
    private \Ess\M2ePro\Model\ResourceModel\Listing\Product $listingProductResource;
    private \Ess\M2ePro\Model\ResourceModel\Amazon\Listing\Product $amazonListingProductResource;
    private \Ess\M2ePro\Model\Amazon\Account\Repository $amazonAccountRepository;
    private \Ess\M2ePro\Model\ResourceModel\Listing $listingResource;

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

        $sources = $this->collectSources($this->amazonAccountRepository->retrieveByMerchantId($merchantId));
        if (empty($sources)) {
            return $itemCollection;
        }

        $itemsBySource = array_map(
            fn($source, $accountIds) => $this->retrieveItems($source, $accountIds),
            array_keys($sources),
            $sources
        );

        $summarizedItems = $this->summarizeItems($itemsBySource);
        $this->addToCollection($summarizedItems, $itemCollection, $merchantId);

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
                'asin' => sprintf('alp.%s', AmazonListingProductResource::COLUMN_GENERAL_ID),
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
        $collection->getSelect()
                   ->group(\Magento\Inventory\Model\ResourceModel\SourceItem::ID_FIELD_NAME);

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

    private function collectSources(array $accounts): array
    {
        $sources = [];

        foreach ($accounts as $account) {
            if (!$account->isEnabledFbaInventoryMode()) {
                continue;
            }

            $sourceName = $account->getManageFbaInventorySourceName();
            if (!$sourceName) {
                continue;
            }

            if (!isset($sources[$sourceName])) {
                $sources[$sourceName] = [];
            }

            $sources[$sourceName][] = $account->getAccountId();
        }

        return $sources;
    }

    private function summarizeItems(array $itemsBySource): array
    {
        $summary = [];
        foreach ($itemsBySource as $sourceItems) {
            foreach ($sourceItems as $item) {
                $channelSku = $item->getDataByKey('channel_sku');
                $onlineAfnQty = (int)$item->getDataByKey('online_afn_qty');

                if (isset($summary[$channelSku])) {
                    $summary[$channelSku]['online_afn_qty'] += $onlineAfnQty;
                } else {
                    $summary[$channelSku] = [
                        'asin' => $item->getDataByKey('asin') ?: null,
                        'channel_sku' => $channelSku,
                        'magento_product_entity_id' => (int)$item->getDataByKey('magento_product_entity_id'),
                        'magento_product_sku' => $item->getDataByKey('magento_product_sku'),
                        'online_afn_qty' => $onlineAfnQty,
                    ];
                }
            }
        }

        return $summary;
    }

    /**
     * @psalm-suppress UndefinedClass
     */
    private function addToCollection(
        array $summarizedItems,
        \M2E\AmazonMcf\Model\Provider\Amazon\Product\ItemCollection $itemCollection,
        string $merchantId
    ): void {
        foreach ($summarizedItems as $itemData) {
            $itemCollection->add(
                $this->createItem(
                    $merchantId,
                    $itemData['channel_sku'],
                    $itemData['magento_product_entity_id'],
                    $itemData['magento_product_sku'],
                    $itemData['online_afn_qty'],
                    $itemData['asin']
                )
            );
        }
    }
}

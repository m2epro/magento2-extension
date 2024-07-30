<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Bundle\Options\Mapping\ChangeHandler;

class AffectedProducts
{
    private \Ess\M2ePro\Helper\Module\Database\Structure $databaseHelper;
    private \Ess\M2ePro\Model\ResourceModel\Listing\Product $listingProductResource;
    private \Magento\Framework\App\ResourceConnection $resourceConnection;
    private \Ess\M2ePro\Model\ResourceModel\Listing\Product\CollectionFactory $collectionFactory;

    public function __construct(
        \Ess\M2ePro\Helper\Module\Database\Structure $databaseHelper,
        \Ess\M2ePro\Model\ResourceModel\Listing\Product $listingProductResource,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Model\ResourceModel\Listing\Product\CollectionFactory $collectionFactory
    ) {
        $this->databaseHelper = $databaseHelper;
        $this->listingProductResource = $listingProductResource;
        $this->resourceConnection = $resourceConnection;
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * @return \Ess\M2ePro\Model\Listing\Product[]
     */
    public function getListingProducts(array $optionTitles): array
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter(
            \Ess\M2ePro\Model\ResourceModel\Listing\Product::COLUMN_ID,
            ['in' => $this->getListingProductIds($optionTitles)]
        );

        return array_values($collection->getItems());
    }

    private function getListingProductIds(array $optionTitles): array
    {
        $select = $this->resourceConnection->getConnection()->select();

        $bundleOptionsValueTableName = $this
            ->databaseHelper
            ->getTableNameWithPrefix('catalog_product_bundle_option_value');

        $magentoProductTable = $this
            ->databaseHelper
            ->getTableNameWithPrefix('catalog_product_entity');

        $select
            ->distinct()
            ->from(
                ['option_value' => $bundleOptionsValueTableName],
                []
            )
            ->joinInner(
                ['magento_product' => $magentoProductTable],
                'option_value.parent_product_id = magento_product.entity_id',
                []
            )
            ->joinInner(
                ['listing_product' => $this->listingProductResource->getMainTable()],
                sprintf(
                    'listing_product.%s = magento_product.entity_id',
                    \Ess\M2ePro\Model\ResourceModel\Listing\Product::PRODUCT_ID_FIELD
                ),
                [
                    'listing_product_id' => \Ess\M2ePro\Model\ResourceModel\Listing\Product::COLUMN_ID,
                ],
            )
            ->where('option_value.store_id = ?', \Magento\Store\Model\Store::DEFAULT_STORE_ID)
            ->where('option_value.title IN (?)', $optionTitles)
            ->where(
                sprintf(
                    "listing_product.%s = ?",
                    \Ess\M2ePro\Model\ResourceModel\Listing\Product::COMPONENT_MODE_FIELD
                ),
                \Ess\M2ePro\Helper\Component\Ebay::NICK
            );

        $stmt = $select->query();

        $ids = [];
        while ($row = $stmt->fetch()) {
            $ids[] = (int)$row['listing_product_id'];
        }

        return $ids;
    }
}

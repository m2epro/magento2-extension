<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Bundle\Options\Mapping;

use Ess\M2ePro\Model\ResourceModel\Ebay\Bundle\Options\Mapping as BundleOptionMappingResource;

class FormDataLoader
{
    private \Ess\M2ePro\Helper\Module\Database\Structure $databaseHelper;
    private \Ess\M2ePro\Model\ResourceModel\Listing\Product $listingProductResource;
    private BundleOptionMappingResource $optionsMappingResource;
    private \Magento\Framework\App\ResourceConnection $resourceConnection;
    private \Ess\M2ePro\Model\Ebay\Bundle\Options\Mapping\FormDataLoader\FormOptionFactory $formBundleOptionFactory;

    public function __construct(
        \Ess\M2ePro\Helper\Module\Database\Structure $databaseHelper,
        \Ess\M2ePro\Model\ResourceModel\Listing\Product $listingProductResource,
        BundleOptionMappingResource $optionsMappingResource,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Model\Ebay\Bundle\Options\Mapping\FormDataLoader\FormOptionFactory $formBundleOptionFactory
    ) {
        $this->databaseHelper = $databaseHelper;
        $this->listingProductResource = $listingProductResource;
        $this->optionsMappingResource = $optionsMappingResource;
        $this->resourceConnection = $resourceConnection;
        $this->formBundleOptionFactory = $formBundleOptionFactory;
    }

    /**
     * @return \Ess\M2ePro\Model\Ebay\Bundle\Options\Mapping\FormDataLoader\FormOption[]
     */
    public function getBundleOptions(): array
    {
        $select = $this->resourceConnection->getConnection()->select();

        $bundleOptionsValueTableName = $this
            ->databaseHelper
            ->getTableNameWithPrefix('catalog_product_bundle_option_value');

        $select
            ->from(
                ['option_value' => $bundleOptionsValueTableName],
                [
                    'title' => 'title',
                ]
            )->joinLeft(
                ['listing_product' => $this->listingProductResource->getMainTable()],
                sprintf(
                    "listing_product.%s = option_value.parent_product_id AND listing_product.%s = '%s'",
                    \Ess\M2ePro\Model\ResourceModel\Listing\Product::PRODUCT_ID_FIELD,
                    \Ess\M2ePro\Model\ResourceModel\Listing\Product::COMPONENT_MODE_FIELD,
                    \Ess\M2ePro\Helper\Component\Ebay::NICK
                ),
                [
                    'used_in_listing' => new \Zend_Db_Expr('MAX(IF (listing_product.id, 1, 0))')
                ]
            )->joinLeft(
                ['mapping' => $this->optionsMappingResource->getMainTable()],
                sprintf(
                    'mapping.%s = option_value.title',
                    BundleOptionMappingResource::COLUMN_OPTION_TITLE
                ),
                [
                    'mapping_attribute_code' => BundleOptionMappingResource::COLUMN_ATTRIBUTE_CODE,
                ],
            )
            ->where('option_value.store_id = ?', \Magento\Store\Model\Store::DEFAULT_STORE_ID)
            ->group('option_value.title')
            ->order(new \Zend_Db_Expr('MAX(IF (listing_product.id, 1, 0)) DESC, option_value.title'))
        ;

        $stmt = $select->query();

        $options = [];
        while ($row = $stmt->fetch()) {
            $options[] = $this->formBundleOptionFactory->create(
                $row['title'],
                $row['mapping_attribute_code'],
                (bool)$row['used_in_listing']
            );
        }

        return $options;
    }
}

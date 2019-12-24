<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\MigrationFromMagento1;

/**
 * Class \Ess\M2ePro\Setup\MigrationFromMagento1\Mapper
 */
class Mapper
{
    const MAP_PREFIX = 'mtm2map';

    private $storeManager;
    private $categoryCollectionFactory;
    private $activeRecordFactory;
    private $resourceConnection;

    //########################################

    /**
     * Mapper constructor.
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryCollectionFactory
     * @param \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     */
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryCollectionFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Magento\Framework\App\ResourceConnection $resourceConnection
    ) {
        $this->storeManager = $storeManager;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->activeRecordFactory = $activeRecordFactory;
        $this->resourceConnection = $resourceConnection;
    }

    //########################################

    public function map()
    {
        $this->products();
        $this->orders();
        $this->stores();
        $this->categories();
    }

    //########################################

    private function products()
    {
        if (!$this->getConnection()->isTableExists($this->getMapTableName('magento_products'))) {
            return;
        }

        $tablesWithRelations = [
            'm2epro_listing_product',
            'm2epro_listing_other',
            'm2epro_listing_product_variation_option',
            'm2epro_amazon_item',
            'm2epro_ebay_item',
            'm2epro_walmart_item',
            'm2epro_order_item',
            'm2epro_order_matching'
        ];

        $mapTable         = $this->getMapTableName('magento_products');
        $catalogTableName = $this->resourceConnection->getTableName('catalog_product_entity');

        foreach ($tablesWithRelations as $tableWithRelation) {
            $tableName = $this->resourceConnection->getTableName($tableWithRelation);

            if (!$this->getConnection()->isTableExists($tableName)) {
                continue;
            }

            $select = $this->getConnection()->select()
                           ->joinInner(['map_table' => $mapTable], 'main_table.product_id=map_table.product_id', '')
                           ->joinInner(
                               ['cp' => $catalogTableName],
                               'map_table.sku=cp.sku',
                               ['product_id' => 'entity_id']
                           )
                           ->where('map_table.product_id != cp.entity_id');

            $this->getConnection()->query(
                $this->getConnection()->updateFromSelect($select, ['main_table' => $tableName])
            );
        }

        $this->getConnection()->dropTable($this->getMapTableName('magento_products'));
    }

    // ---------------------------------------

    private function orders()
    {
        $mapTable = $this->getMapTableName('magento_orders');

        if (!$this->getConnection()->isTableExists($mapTable)) {
            return;
        }

        $orderTableName = $this->resourceConnection->getTableName('sales_order');

        $select = $this->getConnection()->select()
                       ->joinInner(['map_table' => $mapTable], 'main_table.magento_order_id=map_table.order_id', '')
                       ->joinInner(
                           ['so' => $orderTableName],
                           'map_table.magento_order_num=so.increment_id',
                           ['magento_order_id' => 'entity_id']
                       )
                       ->where('map_table.order_id != so.entity_id');

        $this->getConnection()->query(
            $this->getConnection()->updateFromSelect(
                $select,
                ['main_table' => $this->resourceConnection->getTableName('m2epro_order')]
            )
        );

        $this->getConnection()->dropTable($this->getMapTableName('magento_orders'));
    }

    // ---------------------------------------

    private function stores()
    {
        if (!$this->getConnection()->isTableExists($this->getMapTableName('magento_stores'))) {
            return;
        }

        $tablesWithRelations = [
            'm2epro_listing',
            'm2epro_order',
            'm2epro_amazon_item',
            'm2epro_ebay_item',
            'm2epro_walmart_item',
        ];

        $mapTable       = $this->getMapTableName('magento_stores');
        $storeTableName = $this->resourceConnection->getTableName('store');

        foreach ($tablesWithRelations as $tableWithRelation) {
            $tableName = $this->resourceConnection->getTableName($tableWithRelation);

            if (!$this->getConnection()->isTableExists($tableName)) {
                continue;
            }

            $select = $this->getConnection()->select()
                           ->joinInner(['map_table' => $mapTable], 'main_table.store_id=map_table.store_id', '')
                           ->joinInner(['s' => $storeTableName], 'map_table.code=s.code', 'store_id')
                           ->where('map_table.store_id != s.store_id');

            $this->getConnection()->query(
                $this->getConnection()->updateFromSelect($select, ['main_table' => $tableName])
            );
        }

        $this->getConnection()->dropTable($this->getMapTableName('magento_stores'));
    }

    // ---------------------------------------

    /**
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function categories()
    {
        $mapTable = $this->getMapTableName('magento_categories');

        if (!$this->getConnection()->isTableExists($mapTable)) {
            return;
        }

        $autoCategoryCollection = $this->activeRecordFactory->getObject('Listing_Auto_Category')->getCollection();
        $categoriesIds          = $autoCategoryCollection->getColumnValues('category_id');

        if (!empty($categoriesIds)) {
            $select = $this->getConnection()->select()->from($mapTable);
            $mapData = $this->getConnection()->fetchAssoc($select);

            $categoriesData = [];
            foreach ($mapData as $row) {
                $categoriesData[$row['category_path']] = $row['category_id'];
            }

            /** @var \Magento\Catalog\Model\ResourceModel\Category\Collection $collection */
            $collection = $this->categoryCollectionFactory->create();

            $storeId = $this->storeManager->getStore()->getId();
            $this->storeManager->setCurrentStore(\Magento\Store\Model\Store::DEFAULT_STORE_ID);

            foreach ($collection as $category) {
                /** @var \Magento\Catalog\Model\Category $category */

                $path        = [];
                $pathInStore = $category->getPathInStore();
                $pathIds     = array_reverse(explode(',', $pathInStore));

                $categories = $category->getParentCategories();

                foreach ($pathIds as $categoryId) {
                    if (isset($categories[$categoryId]) && $categories[$categoryId]->getName()) {
                        $path[] = $categories[$categoryId]->getName();
                    }
                }

                $categoryPath = implode('/', $path);

                if (isset($categoriesData[$categoryPath])) {
                    $this->getConnection()->update(
                        $this->resourceConnection->getTableName('m2epro_listing_auto_category'),
                        ['category_id' => $category->getEntityId()],
                        ['category_id' => (int)$categoriesData[$categoryPath]]
                    );
                }
            }

            $this->storeManager->setCurrentStore($storeId);
        }

        $this->getConnection()->dropTable($this->getMapTableName('magento_categories'));
    }

    //########################################

    /**
     * @return \Magento\Framework\DB\Adapter\AdapterInterface
     */
    private function getConnection()
    {
        return $this->resourceConnection->getConnection();
    }

    /**
     * @param $name
     * @return string
     */
    private function getMapTableName($name)
    {
        return $this->resourceConnection->getTableName('m2epro__' . self::MAP_PREFIX . '_' . $name);
    }

    //########################################
}

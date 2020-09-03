<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\MigrationFromMagento1\PreconditionsChecker;

use Ess\M2ePro\Setup\MigrationFromMagento1\MappingTablesDownloader;

/**
 * Class \Ess\M2ePro\Setup\MigrationFromMagento1\PreconditionsChecker\UnexpectedlyCopied
 */
class UnexpectedlyCopied extends AbstractModel
{
    const EXCEPTION_CODE_WRONG_VERSION       = 1;
    const EXCEPTION_CODE_MAPPING_VIOLATED    = 2;
    const EXCEPTION_CODE_TABLES_DO_NOT_EXIST = 3;

    /** @var MappingTablesDownloader */
    protected $mappingTablesDownloader;

    //########################################

    public function __construct(
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        MappingTablesDownloader $mappingTablesDownloader
    ) {
        parent::__construct($helperFactory, $modelFactory, $activeRecordFactory, $resourceConnection);

        $this->mappingTablesDownloader = $mappingTablesDownloader;
    }

    //########################################

    /**
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Zend_Db_Statement_Exception
     */
    public function checkPreconditions()
    {
        $this->checkVersion();

        $configTableName = $this->getOldTablesPrefix() . 'm2epro_config';
        if (!$this->getConnection()->isTableExists($configTableName)) {
            throw new \Ess\M2ePro\Model\Exception\Logic(
                'Config table does not exist',
                [],
                self::EXCEPTION_CODE_TABLES_DO_NOT_EXIST
            );
        }

        if ($this->mappingTablesDownloader->isDownloadComplete()) {
            return;
        }

        if (!$this->checkProducts() || !$this->checkOrders() || !$this->checkStores() || !$this->checkCategories()) {
            throw new \Ess\M2ePro\Model\Exception\Logic(
                'Some entities, transferred from Magento 1 database are mapped to wrong Magento 2 entities',
                [],
                self::EXCEPTION_CODE_MAPPING_VIOLATED
            );
        }
    }

    /**
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function checkVersion()
    {
        $setupTableWithPrefix = $this->getOldTablesPrefix() . 'm2epro_setup';

        $select = $this->resourceConnection->getConnection()
            ->select()
            ->from($setupTableWithPrefix, 'version_to')
            ->order('id DESC')
            ->limit(1);
       
        $lastUpgradeVersion = $this->resourceConnection->getConnection()->fetchOne($select);

        if (!$this->compareVersions($lastUpgradeVersion)) {
            throw new \Ess\M2ePro\Model\Exception\Logic(
                'Current version for Magento 1 does not support data migration',
                [],
                self::EXCEPTION_CODE_WRONG_VERSION
            );
        }
    }

    /**
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Zend_Db_Statement_Exception
     */
    protected function checkProducts()
    {
        return $this->checkSkuMatchingByComponent('ebay', 'online_sku') &&
            $this->checkSkuMatchingByComponent('amazon', 'sku') &&
            $this->checkSkuMatchingByComponent('walmart', 'sku');
    }

    /**
     * @param $component
     * @param $skuField
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Zend_Db_Statement_Exception
     */
    protected function checkSkuMatchingByComponent($component, $skuField)
    {
        $listingProductTable = $this->getOldTablesPrefix() . 'm2epro_listing_product';
        $componentTable = $this->getOldTablesPrefix() . "m2epro_{$component}_listing_product";
        $select = $this->getConnection()
            ->select()
            ->from(['mlp' => $listingProductTable], 'id')
            ->joinLeft(
                [
                    'cpe' => $this->helperFactory->getObject('Module_Database_Structure')
                        ->getTableNameWithPrefix('catalog_product_entity')
                ],
                '(mlp.product_id = cpe.entity_id)'
            )
            ->joinLeft(
                [
                    'mclp' => $componentTable
                ],
                '(mlp.id = mclp.listing_product_id)'
            )
            ->where("mclp.{$skuField} IS NOT NULL AND mclp.{$skuField} <> cpe.sku")
            ->limit(1);

        return !(bool) $select->query()->fetch();
    }

    /**
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Zend_Db_Statement_Exception
     */
    protected function checkOrders()
    {
        $orderTable = $this->getOldTablesPrefix() . 'm2epro_order';
        $magentoOrderTable = $this->helperFactory->getObject('Module_Database_Structure')
            ->getTableNameWithPrefix('sales_order');

        $select = $this->getConnection()->select()
            ->from(['mo' => $orderTable], new \Zend_Db_Expr('MAX(`mo`.`magento_order_id`) > MAX(`so`.`entity_id`)'))
            ->join(['so' => $magentoOrderTable], '');

        return !(bool) $select->query()->fetch(\PDO::FETCH_COLUMN);
    }

    /**
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function checkStores()
    {
        $storeRelatedTables = [
            'm2epro_listing',
            'm2epro_order',
            'm2epro_amazon_item',
            'm2epro_ebay_item',
            'm2epro_walmart_item'
        ];

        foreach ($storeRelatedTables as $storeRelatedTable) {
            $storeRelatedTable = $this->getOldTablesPrefix() . $storeRelatedTable;
            $select = $this->getConnection()
                ->select()
                ->from(['srt' => $storeRelatedTable], 'srt.store_id')
                ->joinLeft(
                    [
                        'store' => $this->helperFactory->getObject('Module_Database_Structure')
                            ->getTableNameWithPrefix('store')
                    ],
                    '(srt.store_id = store.store_id)'
                )
                ->where('store.store_id IS NULL')
                ->group('srt.store_id');

            if ($select->query()->fetchAll(\PDO::FETCH_COLUMN)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Zend_Db_Statement_Exception
     */
    protected function checkCategories()
    {
        $listingProductTable = $this->getOldTablesPrefix() . 'm2epro_listing_auto_category';

        $select = $this->getConnection()
            ->select()
            ->from(['mlac' => $listingProductTable], 'id')
            ->joinLeft(
                [
                    'cce' => $this->helperFactory->getObject('Module_Database_Structure')
                        ->getTableNameWithPrefix('catalog_category_entity')
                ],
                '(mlac.category_id = cce.entity_id)'
            )
            ->where('cce.entity_id IS NULL')
            ->limit(1);

        return !(bool) $select->query()->fetch();
    }

    //########################################
}

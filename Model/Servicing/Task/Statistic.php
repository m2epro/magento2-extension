<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Servicing\Task;

use Magento\InventoryApi\Api\GetSourcesAssignedToStockOrderedByPriorityInterface;
use Magento\InventorySalesApi\Model\GetAssignedSalesChannelsForStockInterface;
use Ess\M2ePro\Helper\Component\Ebay as HelperEbay;
use Ess\M2ePro\Helper\Component\Amazon as HelperAmazon;

// @codingStandardsIgnoreFile

/**
 * Class \Ess\M2ePro\Model\Servicing\Task\Statistic
 */
class Statistic extends \Ess\M2ePro\Model\Servicing\Task
{
    const RUN_INTERVAL = 604800; // 1 week

    protected $transactionCollectionFactory;

    protected $creditmemoCollectionFactory;

    protected $shipmentCollectionFactory;

    protected $invoiceCollectionFactory;

    protected $categoryFactory;

    protected $attributeSetFactory;

    protected $productFactory;

    protected $attributeCollection;

    protected $moduleList;

    protected $moduleManager;

    protected $synchronizationConfig;

    protected $objectManager;

    //########################################

    public function __construct(
        \Magento\Sales\Model\ResourceModel\Order\Payment\Transaction\CollectionFactory $transactionCollectionFactory,
        \Magento\Sales\Model\ResourceModel\Order\Creditmemo\CollectionFactory $creditmemoCollectionFactory,
        \Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory $shipmentCollectionFactory,
        \Magento\Sales\Model\ResourceModel\Order\Invoice\CollectionFactory $invoiceCollectionFactory,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        \Magento\Eav\Model\Entity\Attribute\SetFactory $attributeSetFactory,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory $attributeCollection,
        \Magento\Framework\Module\ModuleListInterface $moduleList,
        \Magento\Framework\Module\Manager $moduleManager,
        \Magento\Eav\Model\Config $config,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Ess\M2ePro\Model\Config\Manager\Synchronization $synchronizationConfig,
        \Ess\M2ePro\Model\Config\Manager\Cache $cacheConfig,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\App\ResourceConnection $resource,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory,
        \Magento\Framework\ObjectManagerInterface $objectManager
    ) {
        $this->transactionCollectionFactory = $transactionCollectionFactory;
        $this->creditmemoCollectionFactory = $creditmemoCollectionFactory;
        $this->shipmentCollectionFactory = $shipmentCollectionFactory;
        $this->invoiceCollectionFactory = $invoiceCollectionFactory;
        $this->categoryFactory = $categoryFactory;
        $this->attributeSetFactory = $attributeSetFactory;
        $this->productFactory = $productFactory;
        $this->attributeCollection = $attributeCollection;
        $this->moduleList = $moduleList;
        $this->moduleManager = $moduleManager;
        $this->synchronizationConfig = $synchronizationConfig;
        $this->objectManager = $objectManager;
        parent::__construct(
            $config,
            $cacheConfig,
            $storeManager,
            $modelFactory,
            $helperFactory,
            $resource,
            $activeRecordFactory,
            $parentFactory
        );
    }

    //########################################

    /**
     * @return string
     */
    public function getPublicNick()
    {
        return 'statistic';
    }

    //########################################

    /**
     * @return bool
     */
    public function isAllowed()
    {
        $cacheConfig = $this->cacheConfig;

        $lastRun = $cacheConfig->getGroupValue('/servicing/statistic/', 'last_run');

        if ($this->getInitiator() === \Ess\M2ePro\Helper\Data::INITIATOR_DEVELOPER ||
            $lastRun === null ||
            $this->getHelper('Data')->getCurrentGmtDate(true) > strtotime($lastRun) + self::RUN_INTERVAL) {
            $cacheConfig->setGroupValue(
                '/servicing/statistic/',
                'last_run',
                $this->getHelper('Data')->getCurrentGmtDate()
            );

            return true;
        }

        return false;
    }

    //########################################

    /**
     * @return array
     */
    public function getRequestData()
    {
        return [
            'statistics' => [
                'server'    => $this->getServerRequestPart(),
                'magento'   => $this->getMagentoRequestPart(),
                'extension' => $this->getExtensionRequestPart(),
            ],
        ];
    }

    public function processResponseData(array $data)
    {
        return null;
    }

    //########################################

    private function fillUpDataByMethod(array &$data, $method)
    {
        try {
            if (is_callable([$this, $method])) {
                $this->$method($data);
            }
        } catch (\Exception $e) {
            $this->getHelper('Module\Exception')->process($e);
        }
    }

    //########################################

    private function getServerRequestPart()
    {
        $data = [];

        $this->fillUpDataByMethod($data, 'appendServerSystemInfo');
        $this->fillUpDataByMethod($data, 'appendServerPhpInfo');
        $this->fillUpDataByMethod($data, 'appendServerMysqlInfo');

        return $data;
    }

    // ---------------------------------------

    private function appendServerSystemInfo(&$data)
    {
        $data['name'] = $this->getHelper('Client')->getSystem();
    }

    private function appendServerPhpInfo(&$data)
    {
        $phpSettings = $this->getHelper('Client')->getPhpSettings();

        $data['php']['version']            = $this->getHelper('Client')->getPhpVersion();
        $data['php']['server_api']         = $this->getHelper('Client')->getPhpApiName();
        $data['php']['memory_limit']       = $phpSettings['memory_limit'];
        $data['php']['max_execution_time'] = $phpSettings['max_execution_time'];
    }

    private function appendServerMysqlInfo(&$data)
    {
        $mySqlSettings = $this->getHelper('Client')->getMysqlSettings();

        $data['mysql']['version']         = $this->getHelper('Client')->getMysqlVersion();
        $data['mysql']['api']             = $this->getHelper('Client')->getMysqlApiName();
        $data['mysql']['database_name']   = $this->getHelper('Magento')->getDatabaseName();
        $data['mysql']['table_prefix']    = $this->getHelper('Magento')->getDatabaseTablesPrefix();
        $data['mysql']['connect_timeout'] = $mySqlSettings['connect_timeout'];
        $data['mysql']['wait_timeout']    = $mySqlSettings['wait_timeout'];
    }

    //########################################

    private function getMagentoRequestPart()
    {
        $data = [];

        $this->fillUpDataByMethod($data, 'appendMagentoSystemInfo');

        $this->fillUpDataByMethod($data, 'appendMagentoModulesInfo');
        $this->fillUpDataByMethod($data, 'appendMagentoStoresInfo');
        $this->fillUpDataByMethod($data, 'appendMagentoStocksInfo');

        $this->fillUpDataByMethod($data, 'appendMagentoAttributesInfo');
        $this->fillUpDataByMethod($data, 'appendMagentoProductsInfo');
        $this->fillUpDataByMethod($data, 'appendMagentoOrdersInfo');

        return $data;
    }

    // ---------------------------------------

    private function appendMagentoSystemInfo(&$data)
    {
        $data['info']['edition']  = $this->getHelper('Magento')->getEditionName();
        $data['info']['version']  = $this->getHelper('Magento')->getVersion();
        $data['info']['location'] = $this->getHelper('Magento')->getLocation();

        $data['settings']['compilation']   = defined('COMPILER_INCLUDE_PATH');
        //$data['settings']['cache_backend'] = $this->getHelper('Client\Cache')->getBackend();
        $data['settings']['secret_key']    = $this->getHelper('Magento')->isSecretKeyToUrl();
    }

    private function appendMagentoModulesInfo(&$data)
    {
        foreach ($this->moduleList->getAll() as $module => $moduleData) {
            $data['modules'][$module] = [
                'name'    => $module,
                'version' => isset($moduleData['setup_version']) ? $moduleData['setup_version'] : null,
                'status'  => (int) $this->moduleManager->isEnabled($module)
            ];
        }
    }

    private function appendMagentoStoresInfo(&$data)
    {
        foreach ($this->storeManager->getWebsites() as $website) {
            /** @var \Magento\Store\Model\Website $website */
            foreach ($website->getGroups() as $group) {
                /** @var \Magento\Store\Model\Group $group */
                foreach ($group->getStores() as $store) {
                    /** @var \Magento\Store\Model\Store $store */
                    $data['stores'][$website->getName()][$group->getName()][] = $store->getName();
                }
            }
        }
    }

    private function appendMagentoStocksInfo(&$data)
    {
        if (!$this->getHelper('Magento')->isMSISupportingVersion()) {
            return;
        }

        $stocksData = $this->resource
           ->getConnection()
           ->select()
           ->from(
               $this->getHelper('Module_Database_Structure')->getTableNameWithPrefix('inventory_stock')
           )
           ->query()
           ->fetchAll();

        /** @var GetSourcesAssignedToStockOrderedByPriorityInterface $getSources */
        /** @var GetAssignedSalesChannelsForStockInterface $getAssignedChannels */
        $getSources = $this->objectManager->get(GetSourcesAssignedToStockOrderedByPriorityInterface::class);
        $getAssignedChannels = $this->objectManager->get(GetAssignedSalesChannelsForStockInterface::class);

        foreach ($stocksData as $stockData) {
            try {
                $sources = $getSources->execute($stockData['stock_id']);
            } catch (\Exception $exception) {
                $sources = [];
            }

            foreach ($sources as $source) {
                $data['stocks'][$stockData['name']]['sources'][] = [
                    'source_name' => $source->getName(),
                    'source_code' => $source->getSourceCode(),
                    'is_enabled' => (int)$source->isEnabled()
                ];
            }

            foreach ($getAssignedChannels->execute($stockData['stock_id']) as $salesChannel) {
                $data['stocks'][$stockData['name']]['sales_channels'][] = [
                    'channel_type' => $salesChannel->getType(),
                    'channel_code' => $salesChannel->getCode()
                ];
            }
        }
    }

    private function appendMagentoAttributesInfo(&$data)
    {
        $collection = $this->attributeCollection->create()->addVisibleFilter();
        $data['attributes']['amount'] = $collection->getSize();

        $entityTypeId = $this->productFactory->create()->getResource()->getTypeId();
        $collection = $this->attributeSetFactory->create()
            ->getCollection()
            ->setEntityTypeFilter($entityTypeId);
        $data['attribute_sets']['amount'] = $collection->getSize();

        $collection = $this->categoryFactory->create()->getCollection();
        $data['categories']['amount'] = $collection->getSize();
    }

    private function appendMagentoProductsInfo(&$data)
    {
        // Count of Products
        $queryStmt = $this->resource->getConnection()
              ->select()
              ->from(
                  $this->getHelper('Module_Database_Structure')->getTableNameWithPrefix('catalog_product_entity'),
                  [
                     'count' => new \Zend_Db_Expr('COUNT(*)'),
                     'type'  => 'type_id'
                  ]
              )
              ->group('type_id')
              ->query();

        $data['products']['total'] = 0;

        while ($row = $queryStmt->fetch()) {
            $data['products']['total'] += (int)$row['count'];
            $data['products']['types'][$row['type']]['amount'] = (int)$row['count'];
        }
        // ---------------------------------------

        // QTY / Stock Availability {simple}
        $queryStmt = $this->resource->getConnection()
              ->select()
              ->from(
                  [
                      'stock_item' => $this->getHelper('Module_Database_Structure')
                          ->getTableNameWithPrefix('cataloginventory_stock_item')
                  ],
                  [
                     'min_qty'     => new \Zend_Db_Expr('MIN(stock_item.qty)'),
                     'max_qty'     => new \Zend_Db_Expr('MAX(stock_item.qty)'),
                     'avg_qty'     => new \Zend_Db_Expr('AVG(stock_item.qty)'),
                     'count'       => new \Zend_Db_Expr('COUNT(*)'),
                     'is_in_stock' => 'stock_item.is_in_stock'
                  ]
              )
              ->joinLeft(
                  [
                      'catalog_product' => $this->getHelper('Module_Database_Structure')
                          ->getTableNameWithPrefix('catalog_product_entity')
                  ],
                  'stock_item.product_id = catalog_product.entity_id',
                  []
              )
              ->where('catalog_product.type_id = ?', 'simple')
              ->group('is_in_stock')
              ->query();

        $data['products']['qty']['min'] = 0;
        $data['products']['qty']['max'] = 0;
        $data['products']['qty']['avg'] = 0;

        $data['products']['stock_availability']['min'] = 0;
        $data['products']['stock_availability']['out'] = 0;

        while ($row = $queryStmt->fetch()) {
            $data['products']['qty']['min'] += (int)$row['min_qty'];
            $data['products']['qty']['max'] += (int)$row['max_qty'];
            $data['products']['qty']['avg'] += (int)$row['avg_qty'];

            (int)$row['is_in_stock'] == 1 ? $data['products']['stock_availability']['min'] += (int)$row['count']
                                          : $data['products']['stock_availability']['out'] += (int)$row['count'];
        }

        // Prices {simple}
        $result = $this->resource->getConnection()
              ->select()
              ->from(
                  $this->getHelper('Module_Database_Structure')->getTableNameWithPrefix('catalog_product_index_price'),
                  [
                     'min_price' => new \Zend_Db_Expr('MIN(price)'),
                     'max_price' => new \Zend_Db_Expr('MAX(price)'),
                     'avg_price' => new \Zend_Db_Expr('AVG(price)')
                  ]
              )
              ->where('website_id = ?', $this->storeManager->getWebsite(true)->getId())
              ->query()
              ->fetch();

        $data['products']['price']['min'] = round($result['min_price'], 2);
        $data['products']['price']['max'] = round($result['max_price'], 2);
        $data['products']['price']['avg'] = round($result['avg_price'], 2);
        // ---------------------------------------
    }

    private function appendMagentoOrdersInfo(&$data)
    {
        // Count of Orders
        $queryStmt = $this->resource->getConnection()
              ->select()
              ->from(
                  $this->getHelper('Module_Database_Structure')->getTableNameWithPrefix('sales_order'),
                  [
                     'count'  => new \Zend_Db_Expr('COUNT(*)'),
                     'status' => 'status'
                  ]
              )
              ->group('status')
              ->query();

        $data['orders']['total'] = 0;

        while ($row = $queryStmt->fetch()) {
            $data['orders']['total'] += (int)$row['count'];
            $data['orders']['statuses'][$row['status']]['amount'] = (int)$row['count'];
        }
        // ---------------------------------------

        $collection = $this->invoiceCollectionFactory->create();
        $data['invoices']['amount'] = $collection->getSize();

        $collection = $this->shipmentCollectionFactory->create();
        $data['shipments']['amount'] = $collection->getSize();

        $collection = $this->creditmemoCollectionFactory->create();
        $data['credit_memos']['amount'] = $collection->getSize();

        $collection = $this->transactionCollectionFactory->create();
        $data['transactions']['amount'] = $collection->getSize();
    }

    //########################################

    private function getExtensionRequestPart()
    {
        $data = [];

        $this->fillUpDataByMethod($data, 'appendExtensionSystemInfo');
        $this->fillUpDataByMethod($data, 'appendExtensionM2eProUpdaterModuleInfo');

        $this->fillUpDataByMethod($data, 'appendExtensionTablesInfo');
        $this->fillUpDataByMethod($data, 'appendExtensionSettingsInfo');

        $this->fillUpDataByMethod($data, 'appendExtensionMarketplacesInfo');
        $this->fillUpDataByMethod($data, 'appendExtensionAccountsInfo');

        $this->fillUpDataByMethod($data, 'appendExtensionListingsInfo');
        $this->fillUpDataByMethod($data, 'appendExtensionListingsProductsInfo');
        $this->fillUpDataByMethod($data, 'appendExtensionListingsOtherInfo');

        $this->fillUpDataByMethod($data, 'appendExtensionPoliciesInfo');
        $this->fillUpDataByMethod($data, 'appendExtensionOrdersInfo');

        $this->fillUpDataByMethod($data, 'appendExtensionLogsInfo');

        return $data;
    }

    // ---------------------------------------

    private function appendExtensionSystemInfo(&$data)
    {
        $data['info']['version'] = $this->getHelper('Module')->getPublicVersion();
    }

    private function appendExtensionM2eProUpdaterModuleInfo(&$data)
    {
        $updaterModule = (array)$this->moduleList->getOne('Ess_M2eProUpdater');

        $updaterData['installed'] = (int)$updaterModule;

        if ($updaterData['installed']) {
            $updaterData['status'] = (int)$this->moduleManager->isEnabled($updaterModule['name']);
            $updaterData['version'] = empty($updaterModule['setup_version']) ? '' : $updaterModule['setup_version'];
        }

        $data['info']['m2eproupdater_module'] = $updaterData;
    }

    // ---------------------------------------

    private function appendExtensionTablesInfo(&$data)
    {
        $helper = $this->getHelper('Module_Database_Structure');
        $data['info']['tables'] = [];

        foreach ($helper->getMySqlTables() as $tableName) {
            $data['info']['tables'][$tableName] = [
                'size'   => $helper->getDataLength($tableName),
                'amount' => $helper->getCountOfRecords($tableName),
            ];
        }
    }

    private function appendExtensionSettingsInfo(&$data)
    {
        $config = $this->getHelper('Module')->getConfig();

        $data['settings']['track_direct'] = $this->synchronizationConfig->getGroupValue(
            '/global/magento_products/inspector/',
            'mode'
        );
        $data['settings']['manage_stock_backorders'] = false;

        if ($config->getGroupValue('/product/force_qty/', 'mode')) {
            $data['settings']['manage_stock_backorders'] = $config->getGroupValue('/product/force_qty/', 'value');
        }

        foreach ($this->getHelper('Component')->getComponents() as $componentNick) {
            $tempInfo = [];

            $tempInfo['enabled'] = $config->getGroupValue('/component/'.$componentNick.'/', 'mode');

            if ($componentNick == \Ess\M2ePro\Helper\Component\Ebay::NICK) {
                $tempInfo['mode'] = $config->getGroupValue('/view/ebay/', 'mode');
            }

            $data['settings']['channels'][$componentNick] = $tempInfo;
        }
    }

    // ---------------------------------------

    private function appendExtensionMarketplacesInfo(&$data)
    {
        $data['marketplaces'] = [];

        $collection = $this->activeRecordFactory->getObject('Marketplace')->getCollection();
        $collection->addFieldToFilter('status', \Ess\M2ePro\Model\Marketplace::STATUS_ENABLE);

        /** @var \Ess\M2ePro\Model\Marketplace $item */
        foreach ($collection->getItems() as $item) {
            $data['marketplaces'][$item->getComponentMode()][$item->getNativeId()] = $item->getTitle();
        }

        $amazonMarketplaces = $this->parentFactory->getObject(HelperAmazon::NICK, 'Marketplace')->getCollection();
        $amazonMarketplaces->addFieldToFilter('developer_key', ['notnull' => true]);
        foreach ($amazonMarketplaces->getItems() as $amazonMark) {
            /** @var \Ess\M2ePro\Model\Marketplace $amazonMark */

            $collection = $this->parentFactory->getObject(HelperEbay::NICK, 'Marketplace')->getCollection();
            $collection->addFieldToFilter('title', $amazonMark->getTitle());

            /** @var \Ess\M2ePro\Model\Marketplace $ebayMark */
            $ebayMark = $collection->getFirstItem();
            if (!$ebayMark->getId()) {
                continue;
            }

            $data['marketplaces']['products'][$amazonMark->getCode()] = $this->getFilledProductsByMarketplaceInfo(
                $this->getUniqueProductsByMarketplace(HelperEbay::NICK, $ebayMark->getId()),
                $this->getUniqueProductsByMarketplace(HelperAmazon::NICK, $amazonMark->getId())
            );
        }

        $data['marketplaces']['products']['total'] = $this->getFilledProductsByMarketplaceInfo(
            $this->getUniqueProductsByMarketplace(HelperEbay::NICK, null),
            $this->getUniqueProductsByMarketplace(HelperAmazon::NICK, null)
        );
    }

    protected function getUniqueProductsByMarketplace($component, $marketplaceId)
    {
        $databaseHelper = $this->getHelper('Module_Database_Structure');
        $products = $this->parentFactory->getObject($component, 'Listing_Product')->getCollection();

        if ($marketplaceId !== null) {
            $products->getSelect()->joinInner(
                ['listing' => $databaseHelper->getTableNameWithPrefix('m2epro_listing')],
                'listing.id=main_table.listing_id',
                []
            );
            $products->addFieldToFilter('listing.marketplace_id', $marketplaceId);
        }

        $products->getSelect()->distinct(true);
        $products->getSelect()->reset(\Zend_Db_Select::COLUMNS);
        $products->getSelect()->columns(['product_id']);

        $options = $this->parentFactory->getObject($component, 'Listing_Product_Variation_Option')->getCollection();

        if ($marketplaceId !== null) {
            $options->getSelect()->joinInner(
                ['variation' => $databaseHelper->getTableNameWithPrefix('m2epro_listing_product_variation')],
                'variation.id=main_table.listing_product_variation_id',
                []
            );
            $options->getSelect()->joinInner(
                ['product' => $databaseHelper->getTableNameWithPrefix('m2epro_listing_product')],
                'product.id=variation.listing_product_id',
                []
            );
            $options->getSelect()->joinInner(
                ['listing' => $databaseHelper->getTableNameWithPrefix('m2epro_listing')],
                'listing.id=product.listing_id',
                []
            );
            $options->addFieldToFilter('listing.marketplace_id', $marketplaceId);
        }

        $options->getSelect()->distinct(true);
        $options->getSelect()->reset(\Zend_Db_Select::COLUMNS);
        $options->getSelect()->columns(['product_id']);

        $unionStmt = $this->resource->getConnection()
            ->select()
            ->union(
                [
                    $products->getSelect(),
                    $options->getSelect()
                ]
            )
            ->query();

        $ids = [];
        while ($productId = $unionStmt->fetchColumn()) {
            $ids[] = (int)$productId;
        }

        return $ids;
    }

    protected function getFilledProductsByMarketplaceInfo($ebayIds, $amazonIds)
    {
        $bothIds       = array_intersect($ebayIds, $amazonIds);
        $onlyEbayIds   = array_diff($ebayIds, $amazonIds);
        $onlyAmazonIds = array_diff($amazonIds, $ebayIds);

        return [
            'on-ebay'     => count($ebayIds),
            'on-amazon'   => count($amazonIds),
            'only-ebay'   => count($onlyEbayIds),
            'only-amazon' => count($onlyAmazonIds),
            'both'        => count($bothIds),
        ];
    }

    // ---------------------------------------

    private function appendExtensionAccountsInfo(&$data)
    {
        $data['accounts'] = [];

        $collection = $this->activeRecordFactory->getObject('Account')->getCollection();

        /** @var \Ess\M2ePro\Model\Account $item */
        foreach ($collection->getItems() as $item) {
            $tempInfo = [];
            $childItem = $item->getChildObject();

            if ($item->isComponentModeEbay()) {

                /** @var \Ess\M2ePro\Model\Ebay\Account $childItem */
                $tempInfo['is_production'] = $childItem->isModeProduction();
                $tempInfo['feedbacks_synch'] = $childItem->isFeedbacksReceive();
            }

            if ($item->isComponentModeAmazon()) {

                /** @var \Ess\M2ePro\Model\Amazon\Account $childItem */
                $tempInfo['marketplace'] = $childItem->getMarketplace()->getTitle();
            }

            $tempInfo['other_listings_synch'] = $childItem->isOtherListingsSynchronizationEnabled();

            $data['accounts'][$item->getComponentMode()][$item->getTitle()] = $tempInfo;
        }
    }

    private function appendExtensionListingsInfo(&$data)
    {
        $queryStmt = $this->resource->getConnection()
              ->select()
              ->from(
                  $this->getHelper('Module_Database_Structure')->getTableNameWithPrefix('m2epro_listing'),
                  [
                     'count'          => new \Zend_Db_Expr('COUNT(*)'),
                     'component'      => 'component_mode',
                     'marketplace_id' => 'marketplace_id',
                     'account_id'     => 'account_id',
                     'store_id'       => 'store_id'
                  ]
              )
              ->group([
                          'component_mode',
                          'marketplace_id',
                          'account_id',
                          'store_id'
                      ])
              ->query();

        $data['listings']['total'] = 0;

        $availableComponents = $this->getHelper('Component')->getComponents();
        foreach ($availableComponents as $nick) {
            $data['listings'][$nick]['total'] = 0;
        }

        while ($row = $queryStmt->fetch()) {
            if (!in_array($row['component'], $availableComponents)) {
                continue;
            }

            $data['listings']['total'] += (int)$row['count'];
            $data['listings'][$row['component']]['total'] += (int)$row['count'];

            $markTitle = $this->parentFactory
                ->getObjectLoaded($row['component'], 'Marketplace', $row['marketplace_id'])
                ->getTitle();

            $accountTitle = $this->parentFactory
                ->getObjectLoaded($row['component'], 'Account', $row['account_id'])
               ->getTitle();

            $storePath = (string)$this->getHelper('Magento\Store')->getStorePath($row['store_id']);

            if (!isset($data['listings'][$row['component']]['marketplaces'][$markTitle])) {
                $data['listings'][$row['component']]['marketplaces'][$markTitle] = 0;
            }

            if (!isset($data['listings'][$row['component']]['accounts'][$accountTitle])) {
                $data['listings'][$row['component']]['accounts'][$accountTitle] = 0;
            }

            if (!isset($data['listings']['stores'][$storePath])) {
                $data['listings']['stores'][$storePath] = 0;
            }

            $data['listings'][$row['component']]['marketplaces'][$markTitle] += (int)$row['count'];
            $data['listings'][$row['component']]['accounts'][$accountTitle] += (int)$row['count'];
            $data['listings']['stores'][$storePath] += (int)$row['count'];
        }
    }

    private function appendExtensionListingsProductsInfo(&$data)
    {
        $tableListingProduct       = $this->getHelper('Module_Database_Structure')
            ->getTableNameWithPrefix('m2epro_listing_product');
        $tableAmazonListingProduct = $this->getHelper('Module_Database_Structure')
            ->getTableNameWithPrefix('m2epro_amazon_listing_product');
        $tableProductEntity        = $this->getHelper('Module_Database_Structure')
            ->getTableNameWithPrefix('catalog_product_entity');

        $queryStmt = $this->resource->getConnection()
              ->select()
              ->from(
                  $this->getHelper('Module_Database_Structure')->getTableNameWithPrefix('m2epro_listing'),
                  [
                     'component'      => 'component_mode',
                     'marketplace_id' => 'marketplace_id',
                     'account_id'     => 'account_id',
                     'products_count' => 'products_total_count'
                  ]
              )
              ->query();

        $productTypes = [
            \Ess\M2ePro\Model\Magento\Product::TYPE_SIMPLE_ORIGIN,
            \Ess\M2ePro\Model\Magento\Product::TYPE_CONFIGURABLE_ORIGIN,
            \Ess\M2ePro\Model\Magento\Product::TYPE_BUNDLE_ORIGIN,
            \Ess\M2ePro\Model\Magento\Product::TYPE_GROUPED_ORIGIN,
            \Ess\M2ePro\Model\Magento\Product::TYPE_DOWNLOADABLE_ORIGIN,
            \Ess\M2ePro\Model\Magento\Product::TYPE_VIRTUAL_ORIGIN
        ];

        $data['listings_products']['total'] = 0;

        $availableComponents = $this->getHelper('Component')->getComponents();
        foreach ($availableComponents as $nick) {
            $data['listings_products'][$nick]['total'] = 0;

            foreach ($productTypes as $type) {
                $select = $this->resource->getConnection()->select();
                $select->from(['lp' => $tableListingProduct], ['count(*)'])
                    ->where('component_mode = ?', $nick)
                    ->joinLeft(
                        ['cpe' => $tableProductEntity],
                        'lp.product_id = cpe.entity_id',
                        []
                    )
                    ->where('type_id = ?', $type);

                if ($nick === \Ess\M2ePro\Helper\Component\Amazon::NICK) {
                    $select->joinLeft(
                        ['alp' => $tableAmazonListingProduct],
                        'lp.id = alp.listing_product_id',
                        []
                    )
                        ->where('variation_parent_id IS NULL');
                }

                $data['listings_products'][$nick]['products']['type'][$type] = [
                    'amount' => $this->resource->getConnection()->fetchOne($select)
                ];
            }
        }

        foreach ($productTypes as $type) {
            $amount = 0;
            foreach ($availableComponents as $nick) {
                $amount += $data['listings_products'][$nick]['products']['type'][$type]['amount'];
            }

            $data['listings_products']['products']['type'][$type] = [
                'amount' => $amount
            ];
        }

        while ($row = $queryStmt->fetch()) {
            if (!in_array($row['component'], $availableComponents)) {
                continue;
            }

            $data['listings_products']['total'] += (int)$row['products_count'];
            $data['listings_products'][$row['component']]['total'] += (int)$row['products_count'];

            $markTitle = $this->parentFactory
                ->getObjectLoaded($row['component'], 'Marketplace', $row['marketplace_id'])
                ->getTitle();

            $accountTitle = $this->parentFactory
                ->getObjectLoaded($row['component'], 'Account', $row['account_id'])
                ->getTitle();

            if (!isset($data['listings_products'][$row['component']]['marketplaces'][$markTitle])) {
                $data['listings_products'][$row['component']]['marketplaces'][$markTitle] = 0;
            }

            if (!isset($data['listings_products'][$row['component']]['accounts'][$accountTitle])) {
                $data['listings_products'][$row['component']]['accounts'][$accountTitle] = 0;
            }

            $data['listings_products'][$row['component']]['marketplaces'][$markTitle] += (int)$row['products_count'];
            $data['listings_products'][$row['component']]['accounts'][$accountTitle] += (int)$row['products_count'];
        }
    }

    private function appendExtensionListingsOtherInfo(&$data)
    {
        $queryStmt = $this->resource->getConnection()
              ->select()
              ->from(
                  $this->getHelper('Module_Database_Structure')->getTableNameWithPrefix('m2epro_listing_other'),
                  [
                     'count'          => new \Zend_Db_Expr('COUNT(*)'),
                     'component'      => 'component_mode',
                     'marketplace_id' => 'marketplace_id',
                     'account_id'     => 'account_id',
                  ]
              )
              ->group([
                          'component_mode',
                          'marketplace_id',
                          'account_id'
                      ])
              ->query();

        $data['listings_other']['total'] = 0;

        $availableComponents = $this->getHelper('Component')->getComponents();
        foreach ($availableComponents as $nick) {
            $data['listings_other'][$nick]['total'] = 0;
        }

        while ($row = $queryStmt->fetch()) {
            if (!in_array($row['component'], $availableComponents)) {
                continue;
            }

            $data['listings_other']['total'] += (int)$row['count'];
            $data['listings_other'][$row['component']]['total'] += (int)$row['count'];

            $markTitle = $this->parentFactory
                ->getObjectLoaded($row['component'], 'Marketplace', $row['marketplace_id'])
                ->getTitle();

            $accountTitle = $this->parentFactory
                ->getObjectLoaded($row['component'], 'Account', $row['account_id'])
                ->getTitle();

            if (!isset($data['listings_other'][$row['component']]['marketplaces'][$markTitle])) {
                $data['listings_other'][$row['component']]['marketplaces'][$markTitle] = 0;
            }

            if (!isset($data['listings_other'][$row['component']]['accounts'][$accountTitle])) {
                $data['listings_other'][$row['component']]['accounts'][$accountTitle] = 0;
            }

            $data['listings_other'][$row['component']]['marketplaces'][$markTitle] += (int)$row['count'];
            $data['listings_other'][$row['component']]['accounts'][$accountTitle] += (int)$row['count'];
        }
    }

    private function appendExtensionPoliciesInfo(&$data)
    {
        $data = $this->_appendGeneralPolicyInfoByType('selling_format', 'm2epro_template_selling_format', $data);
        $data = $this->_appendGeneralPolicyInfoByType('synchronization', 'm2epro_template_synchronization', $data);
        $data = $this->_appendGeneralPolicyInfoByType('description', 'm2epro_template_description', $data);

        $data = $this->_appendEbayPolicyInfoByType('payment', 'm2epro_ebay_template_payment', $data);
        $data = $this->_appendEbayPolicyInfoByType('shipping', 'm2epro_ebay_template_shipping', $data);
        $data = $this->_appendEbayPolicyInfoByType('return', 'm2epro_ebay_template_return_policy', $data);

        return $data;
    }

    private function appendExtensionOrdersInfo(&$data)
    {
        $queryStmt = $this->resource->getConnection()
              ->select()
              ->from(
                  $this->getHelper('Module_Database_Structure')->getTableNameWithPrefix('m2epro_order'),
                  [
                     'count'          => new \Zend_Db_Expr('COUNT(*)'),
                     'component'      => 'component_mode',
                     'marketplace_id' => 'marketplace_id',
                     'account_id'     => 'account_id',
                  ]
              )
              ->group([
                          'component_mode',
                          'marketplace_id',
                          'account_id'
                      ])
              ->query();

        $data['orders']['total'] = 0;

        $helper = $this->getHelper('Component');

        $availableComponents = $helper->getComponents();
        foreach ($availableComponents as $nick) {
            $data['orders'][$nick]['total'] = 0;
        }

        while ($row = $queryStmt->fetch()) {
            if (!in_array($row['component'], $availableComponents)) {
                continue;
            }

            $data['orders']['total'] += (int)$row['count'];
            $data['orders'][$row['component']]['total'] += (int)$row['count'];

            $markTitle = $this->parentFactory
                ->getObjectLoaded($row['component'], 'Marketplace', $row['marketplace_id'])
                ->getTitle();

            $accountTitle = $this->parentFactory
                ->getObjectLoaded($row['component'], 'Account', $row['account_id'])
                ->getTitle();

            if (!isset($data['orders'][$row['component']]['marketplaces'][$markTitle])) {
                $data['orders'][$row['component']]['marketplaces'][$markTitle] = 0;
            }

            if (!isset($data['orders'][$row['component']]['accounts'][$accountTitle])) {
                $data['orders'][$row['component']]['accounts'][$accountTitle] = 0;
            }

            $data['orders'][$row['component']]['marketplaces'][$markTitle] += (int)$row['count'];
            $data['orders'][$row['component']]['accounts'][$accountTitle] += (int)$row['count'];
        }

        // Orders types eBay
        $result = $this->resource->getConnection()
               ->select()
               ->from(
                   $this->getHelper('Module_Database_Structure')->getTableNameWithPrefix('m2epro_ebay_order'),
                   ['count' => new \Zend_Db_Expr('COUNT(*)')]
               )
               ->where('checkout_status = ?', \Ess\M2ePro\Model\Ebay\Order::CHECKOUT_STATUS_COMPLETED)
               ->query()
               ->fetchColumn();

        $data['orders']['ebay']['types']['checkout'] = (int)$result;

        $result = $this->resource->getConnection()
               ->select()
               ->from(
                   $this->getHelper('Module_Database_Structure')->getTableNameWithPrefix('m2epro_ebay_order'),
                   ['count' => new \Zend_Db_Expr('COUNT(*)')]
               )
               ->where('shipping_status = ?', \Ess\M2ePro\Model\Ebay\Order::SHIPPING_STATUS_COMPLETED)
               ->query()
               ->fetchColumn();

        $data['orders']['ebay']['types']['shipped'] = (int)$result;

        $result = $this->resource->getConnection()
              ->select()
              ->from(
                  $this->getHelper('Module_Database_Structure')->getTableNameWithPrefix('m2epro_ebay_order'),
                  ['count' => new \Zend_Db_Expr('COUNT(*)')]
              )
              ->where('payment_status = ?', \Ess\M2ePro\Model\Ebay\Order::PAYMENT_STATUS_COMPLETED)
              ->query()
              ->fetchColumn();

        $data['orders']['ebay']['types']['paid'] = (int)$result;
        // ---------------------------------------

        // Orders types Amazon
        $queryStmt = $this->resource->getConnection()
               ->select()
               ->from(
                   $this->getHelper('Module_Database_Structure')->getTableNameWithPrefix('m2epro_amazon_order'),
                   [
                          'count'  => new \Zend_Db_Expr('COUNT(*)'),
                          'status' => 'status'
                   ]
               )
               ->group(['status'])
               ->query();

        $statuses = [
            \Ess\M2ePro\Model\Amazon\Order::STATUS_PENDING             => 'pending',
            \Ess\M2ePro\Model\Amazon\Order::STATUS_UNSHIPPED           => 'unshipped',
            \Ess\M2ePro\Model\Amazon\Order::STATUS_SHIPPED_PARTIALLY   => 'shipped_partially',
            \Ess\M2ePro\Model\Amazon\Order::STATUS_SHIPPED             => 'shipped',
            \Ess\M2ePro\Model\Amazon\Order::STATUS_UNFULFILLABLE       => 'unfulfillable',
            \Ess\M2ePro\Model\Amazon\Order::STATUS_CANCELED            => 'canceled',
            \Ess\M2ePro\Model\Amazon\Order::STATUS_INVOICE_UNCONFIRMED => 'invoice_uncorfirmed'
        ];

        while ($row = $queryStmt->fetch()) {
            $status = $statuses[(int)$row['status']];

            if (!isset($data['orders']['amazon']['types'][$status])) {
                $data['orders']['amazon']['types'][$status] = 0;
            }

            $data['orders']['amazon']['types'][$status] += (int)$row['count'];
        }
        // ---------------------------------------
    }

    private function appendExtensionLogsInfo(&$data)
    {
        $data['logs']['total'] = 0;

        foreach ($this->getHelper('Component')->getComponents() as $nick) {
            $data['logs'][$nick]['total'] = 0;
        }

        $data = $this->_appendLogsInfoByType('listings', 'm2epro_listing_log', $data);
        $data = $this->_appendLogsInfoByType('synchronization', 'm2epro_synchronization_log', $data);
        $data = $this->_appendLogsInfoByType('orders', 'm2epro_order_log', $data);
        $data = $this->_appendLogsInfoByType('other_listings', 'm2epro_listing_other_log', $data);
    }

    //########################################

    private function _appendLogsInfoByType($type, $tableName, $data)
    {
        $queryStmt = $this->resource->getConnection()
              ->select()
              ->from(
                  $this->getHelper('Module_Database_Structure')->getTableNameWithPrefix($tableName),
                  [
                     'count'     => new \Zend_Db_Expr('COUNT(*)'),
                     'component' => 'component_mode'
                  ]
              )
              ->group('component_mode')
              ->query();

        $data['logs']['types'][$type] = 0;

        $availableComponents = $this->getHelper('Component')->getComponents();
        foreach ($availableComponents as $nick) {
            $data['logs'][$nick]['types'][$type] = 0;
        }

        while ($row = $queryStmt->fetch()) {
            if (!in_array($row['component'], $availableComponents)) {
                continue;
            }

            $data['logs']['total'] += (int)$row['count'];
            $data['logs']['types'][$type] += (int)$row['count'];

            $data['logs'][$row['component']]['total'] += (int)$row['count'];
            $data['logs'][$row['component']]['types'][$type] += (int)$row['count'];
        }

        return $data;
    }

    private function _appendGeneralPolicyInfoByType($type, $tableName, $data)
    {
        $queryStmt = $this->resource->getConnection()
              ->select()
              ->from(
                  $this->getHelper('Module_Database_Structure')->getTableNameWithPrefix($tableName),
                  [
                     'count'     => new \Zend_Db_Expr('COUNT(*)'),
                     'component' => 'component_mode'
                  ]
              )
              ->group('component_mode')
              ->query();

        $data['policies'][$type]['total'] = 0;

        $availableComponents = $this->getHelper('Component')->getComponents();
        foreach ($availableComponents as $nick) {
            $data['policies'][$type][$nick] = 0;
        }

        while ($row = $queryStmt->fetch()) {
            if (!in_array($row['component'], $availableComponents)) {
                continue;
            }

            $data['policies'][$type]['total'] += (int)$row['count'];
            $data['policies'][$type][$row['component']] += (int)$row['count'];
        }

        return $data;
    }

    private function _appendEbayPolicyInfoByType($type, $tableName, $data)
    {
        $queryStmt = $this->resource->getConnection()
              ->select()
              ->from(
                  $this->getHelper('Module_Database_Structure')->getTableNameWithPrefix($tableName),
                  [
                     'count' => new \Zend_Db_Expr('COUNT(*)')
                  ]
              )
              ->query();

        $data['policies']['ebay'][$type] = (int)$queryStmt->fetchColumn();

        return $data;
    }

    //########################################
}

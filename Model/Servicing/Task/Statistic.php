<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Servicing\Task;

use Ess\M2ePro\Helper\Component\Amazon as HelperAmazon;
use Ess\M2ePro\Helper\Component\Ebay as HelperEbay;
use Ess\M2ePro\Helper\Component\Walmart as HelperWalmart;
use Magento\InventoryApi\Api\GetSourcesAssignedToStockOrderedByPriorityInterface;
use Magento\InventorySalesApi\Model\GetAssignedSalesChannelsForStockInterface;
use Ess\M2ePro\Model\Cron\Task\System\Servicing\Statistic\InstructionType;

class Statistic implements \Ess\M2ePro\Model\Servicing\TaskInterface
{
    public const NAME = 'statistic';

    public const RUN_INTERVAL = 604800; // 1 week

    /** @var \Magento\Sales\Model\ResourceModel\Order\Payment\Transaction\CollectionFactory */
    private $transactionCollectionFactory;
    /** @var \Magento\Sales\Model\ResourceModel\Order\Creditmemo\CollectionFactory */
    private $creditmemoCollectionFactory;
    /** @var \Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory */
    private $shipmentCollectionFactory;
    /** @var \Magento\Sales\Model\ResourceModel\Order\Invoice\CollectionFactory */
    private $invoiceCollectionFactory;
    /** @var \Magento\Catalog\Model\CategoryFactory */
    private $categoryFactory;
    /** @var \Magento\Eav\Model\Entity\Attribute\SetFactory */
    private $attributeSetFactory;
    /** @var \Magento\Catalog\Model\ProductFactory */
    private $productFactory;
    /** @var \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory */
    private $attributeCollection;
    /** @var \Magento\Framework\Module\ModuleListInterface */
    private $moduleList;
    /** @var \Magento\Framework\Module\Manager */
    private $moduleManager;
    /** @var \Magento\Framework\ObjectManagerInterface */
    private $objectManager;
    /** @var \Magento\Store\Model\StoreManagerInterface */
    private $storeManager;
    /** @var \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory */
    private $parentFactory;
    /** @var \Ess\M2ePro\Model\ActiveRecord\Factory */
    private $activeRecordFactory;
    /** @var \Magento\Framework\App\ResourceConnection */
    private $resource;
    /** @var \Ess\M2ePro\Helper\Module\Configuration */
    private $moduleConfiguration;
    /** @var \Ess\M2ePro\Model\Registry\Manager */
    private $registryManager;
    /** @var \Ess\M2ePro\Helper\Module\Exception */
    private $helperException;
    /** @var \Ess\M2ePro\Helper\Client */
    private $helperClient;
    /** @var \Ess\M2ePro\Helper\Magento */
    private $helperMagento;
    /** @var \Ess\M2ePro\Helper\Module */
    private $helperModule;
    /** @var \Ess\M2ePro\Helper\Module\Database\Structure */
    private $helperDatabaseStructure;
    /** @var \Ess\M2ePro\Helper\Component */
    private $helperComponent;
    /** @var \Ess\M2ePro\Helper\Magento\Store */
    private $helperMagentoStore;

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
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory,
        \Ess\M2ePro\Helper\Module\Configuration $moduleConfiguration,
        \Magento\Framework\App\ResourceConnection $resource,
        \Ess\M2ePro\Model\Registry\Manager $registryManager,
        \Ess\M2ePro\Helper\Module\Exception $helperException,
        \Ess\M2ePro\Helper\Client $helperClient,
        \Ess\M2ePro\Helper\Magento $helperMagento,
        \Ess\M2ePro\Helper\Module $helperModule,
        \Ess\M2ePro\Helper\Module\Database\Structure $helperDatabaseStructure,
        \Ess\M2ePro\Helper\Component $helperComponent,
        \Ess\M2ePro\Helper\Magento\Store $helperMagentoStore
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
        $this->objectManager = $objectManager;
        $this->storeManager = $storeManager;
        $this->activeRecordFactory = $activeRecordFactory;
        $this->parentFactory = $parentFactory;
        $this->moduleConfiguration = $moduleConfiguration;
        $this->resource = $resource;
        $this->registryManager = $registryManager;
        $this->helperException = $helperException;
        $this->helperClient = $helperClient;
        $this->helperMagento = $helperMagento;
        $this->helperModule = $helperModule;
        $this->helperDatabaseStructure = $helperDatabaseStructure;
        $this->helperComponent = $helperComponent;
        $this->helperMagentoStore = $helperMagentoStore;
    }

    // ---------------------------------------

    /**
     * @return string
     */
    public function getServerTaskName(): string
    {
        return self::NAME;
    }

    // ---------------------------------------

    /**
     * @return bool
     * @throws \Exception
     */
    public function isAllowed(): bool
    {
        $lastRun = $this->registryManager->getValue('/servicing/statistic/last_run/');

        if (
            $lastRun === null ||
            \Ess\M2ePro\Helper\Date::createCurrentGmt()->getTimestamp() >
            (int)\Ess\M2ePro\Helper\Date::createDateGmt($lastRun)->format('U') + self::RUN_INTERVAL
        ) {
            $this->registryManager->setValue(
                '/servicing/statistic/last_run/',
                \Ess\M2ePro\Helper\Date::createCurrentGmt()->format('Y-m-d H:i:s')
            );

            return true;
        }

        return false;
    }

    // ---------------------------------------

    /**
     * @return array
     */
    public function getRequestData(): array
    {
        return [
            'statistics' => [
                'server' => $this->getServerRequestPart(),
                'magento' => $this->getMagentoRequestPart(),
                'extension' => $this->getExtensionRequestPart(),
            ],
        ];
    }

    /**
     * @param array $data
     *
     * @return void
     */
    public function processResponseData(array $data): void
    {
    }

    // ---------------------------------------

    /**
     * @param array $data
     * @param $method
     *
     * @return void
     */
    private function fillUpDataByMethod(array &$data, $method): void
    {
        try {
            if (is_callable([$this, $method])) {
                $this->$method($data);
            }
        } catch (\Throwable $e) {
            $this->helperException->process($e);
        }
    }

    // ---------------------------------------

    /**
     * @return array
     */
    private function getServerRequestPart(): array
    {
        $data = [];

        $this->fillUpDataByMethod($data, 'appendServerSystemInfo');
        $this->fillUpDataByMethod($data, 'appendServerPhpInfo');
        $this->fillUpDataByMethod($data, 'appendServerMysqlInfo');

        return $data;
    }

    // ---------------------------------------

    /**
     * @param array $data
     *
     * @return void
     */
    private function appendServerSystemInfo(array &$data): void
    {
        $data['name'] = $this->helperClient->getSystem();
    }

    /**
     * @param array $data
     *
     * @return void
     */
    private function appendServerPhpInfo(array &$data): void
    {
        $phpSettings = $this->helperClient->getPhpSettings();

        $data['php']['version'] = $this->helperClient->getPhpVersion();
        $data['php']['server_api'] = $this->helperClient->getPhpApiName();
        $data['php']['memory_limit'] = $phpSettings['memory_limit'];
        $data['php']['max_execution_time'] = $phpSettings['max_execution_time'];
    }

    /**
     * @param array $data
     *
     * @return void
     */
    private function appendServerMysqlInfo(array &$data): void
    {
        $mySqlSettings = $this->helperClient->getMysqlSettings();

        $data['mysql']['version'] = $this->helperClient->getMysqlVersion();
        $data['mysql']['api'] = $this->helperClient->getMysqlApiName();
        $data['mysql']['table_prefix'] = (bool)$this->helperMagento->getDatabaseTablesPrefix();
        $data['mysql']['connect_timeout'] = (int)$mySqlSettings['connect_timeout'];
        $data['mysql']['wait_timeout'] = (int)$mySqlSettings['wait_timeout'];
    }

    // ---------------------------------------

    /**
     * @return array
     */
    private function getMagentoRequestPart(): array
    {
        $data = [];

        $this->fillUpDataByMethod($data, 'appendMagentoSystemInfo');

        $this->fillUpDataByMethod($data, 'appendMagentoStoresInfo');
        $this->fillUpDataByMethod($data, 'appendMagentoStocksInfo');

        $this->fillUpDataByMethod($data, 'appendMagentoAttributesInfo');
        $this->fillUpDataByMethod($data, 'appendMagentoProductsInfo');
        $this->fillUpDataByMethod($data, 'appendMagentoOrdersInfo');

        return $data;
    }

    // ---------------------------------------

    /**
     * @param array $data
     *
     * @return void
     */
    private function appendMagentoSystemInfo(array &$data): void
    {
        $data['info']['edition'] = $this->helperMagento->getEditionName();
        $data['info']['version'] = $this->helperMagento->getVersion();
        $data['info']['location'] = $this->helperMagento->getLocation();

        $data['settings']['compilation'] = defined('COMPILER_INCLUDE_PATH');
        $data['settings']['secret_key'] = $this->helperMagento->isSecretKeyToUrl();
        $data['settings']['timezone'] = \Ess\M2ePro\Helper\Date::getTimezone()->getConfigTimezone();
    }

    /**
     * @param array $data
     *
     * @return void
     */
    private function appendMagentoStoresInfo(array &$data): void
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

    /**
     * @param array $data
     *
     * @return void
     */
    private function appendMagentoStocksInfo(array &$data): void
    {
        if (!$this->helperMagento->isMSISupportingVersion()) {
            return;
        }

        $stocksData = $this->resource
            ->getConnection()
            ->select()
            ->from(
                $this->helperDatabaseStructure->getTableNameWithPrefix('inventory_stock')
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
            } catch (\Throwable $exception) {
                $sources = [];
            }

            foreach ($sources as $source) {
                $data['stocks'][$stockData['name']]['sources'][] = [
                    'source_name' => $source->getName(),
                    'source_code' => $source->getSourceCode(),
                    'is_enabled' => (int)$source->isEnabled(),
                ];
            }

            foreach ($getAssignedChannels->execute($stockData['stock_id']) as $salesChannel) {
                $data['stocks'][$stockData['name']]['sales_channels'][] = [
                    'channel_type' => $salesChannel->getType(),
                    'channel_code' => $salesChannel->getCode(),
                ];
            }
        }
    }

    /**
     * @param array $data
     *
     * @return void
     */
    private function appendMagentoAttributesInfo(array &$data): void
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

    /**
     * @param array $data
     *
     * @return void
     */
    private function appendMagentoProductsInfo(array &$data): void
    {
        // Count of Products
        $queryStmt = $this->resource->getConnection()
                                    ->select()
                                    ->from(
                                        $this->helperDatabaseStructure->getTableNameWithPrefix(
                                            'catalog_product_entity'
                                        ),
                                        [
                                            'count' => new \Zend_Db_Expr('COUNT(*)'),
                                            'type' => 'type_id',
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
                                            'stock_item' => $this->helperDatabaseStructure
                                                ->getTableNameWithPrefix('cataloginventory_stock_item'),
                                        ],
                                        [
                                            'min_qty' => new \Zend_Db_Expr('MIN(stock_item.qty)'),
                                            'max_qty' => new \Zend_Db_Expr('MAX(stock_item.qty)'),
                                            'avg_qty' => new \Zend_Db_Expr('AVG(stock_item.qty)'),
                                            'count' => new \Zend_Db_Expr('COUNT(*)'),
                                            'is_in_stock' => 'stock_item.is_in_stock',
                                        ]
                                    )
                                    ->joinLeft(
                                        [
                                            'catalog_product' => $this->helperDatabaseStructure
                                                ->getTableNameWithPrefix('catalog_product_entity'),
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
                                     $this->helperDatabaseStructure->getTableNameWithPrefix(
                                         'catalog_product_index_price'
                                     ),
                                     [
                                         'min_price' => new \Zend_Db_Expr('MIN(price)'),
                                         'max_price' => new \Zend_Db_Expr('MAX(price)'),
                                         'avg_price' => new \Zend_Db_Expr('AVG(price)'),
                                     ]
                                 )
                                 ->where('website_id = ?', $this->storeManager->getWebsite(true)->getId())
                                 ->query()
                                 ->fetch();

        $data['products']['price']['min'] = round((float)$result['min_price'], 2);
        $data['products']['price']['max'] = round((float)$result['max_price'], 2);
        $data['products']['price']['avg'] = round((float)$result['avg_price'], 2);
        // ---------------------------------------
    }

    /**
     * @param array $data
     *
     * @return void
     */
    private function appendMagentoOrdersInfo(array &$data): void
    {
        // Count of Orders
        $queryStmt = $this->resource->getConnection()
                                    ->select()
                                    ->from(
                                        $this->helperDatabaseStructure->getTableNameWithPrefix('sales_order'),
                                        [
                                            'count' => new \Zend_Db_Expr('COUNT(*)'),
                                            'status' => 'status',
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

    // ---------------------------------------

    /**
     * @return array
     */
    private function getExtensionRequestPart(): array
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
        $this->fillUpDataByMethod($data, 'appendExtensionListingProductInstructionInfo');
        $this->fillUpDataByMethod($data, 'appendExtensionListingsOtherInfo');

        $this->fillUpDataByMethod($data, 'appendExtensionPoliciesInfo');
        $this->fillUpDataByMethod($data, 'appendExtensionOrdersInfo');

        $this->fillUpDataByMethod($data, 'appendExtensionLogsInfo');

        return $data;
    }

    // ---------------------------------------

    /**
     * @param array $data
     *
     * @return void
     */
    private function appendExtensionSystemInfo(array &$data): void
    {
        $data['info']['version'] = $this->helperModule->getPublicVersion();
    }

    /**
     * @param array $data
     *
     * @return void
     */
    private function appendExtensionM2eProUpdaterModuleInfo(array &$data): void
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

    /**
     * @param array $data
     *
     * @return void
     * @throws \ReflectionException
     */
    private function appendExtensionTablesInfo(array &$data): void
    {
        $helper = $this->helperDatabaseStructure;
        $data['info']['tables'] = [];

        foreach ($helper->getModuleTables() as $tableName) {
            $data['info']['tables'][$tableName] = [
                'size' => $helper->getDataLength($tableName),
                'amount' => $helper->getCountOfRecords($tableName),
            ];
        }
    }

    /**
     * @param array $data
     *
     * @return void
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    private function appendExtensionSettingsInfo(array &$data): void
    {
        $config = $this->helperModule->getConfig();
        $settings = [];

        $settings['products_show_thumbnails'] = $this->moduleConfiguration->getViewShowProductsThumbnailsMode();
        $settings['block_notices_show'] = $this->moduleConfiguration->getViewShowBlockNoticesMode();
        $settings['manage_stock_backorders'] = $this->moduleConfiguration->getProductForceQtyMode();
        $settings['manage_stock_backorders_qty'] = $this->moduleConfiguration->getProductForceQtyValue();
        $settings['price_convert_mode'] = $this->moduleConfiguration->getMagentoAttributePriceTypeConvertingMode();
        $settings['inspector_mode'] = $this->moduleConfiguration->getListingProductInspectorMode();

        $settings['logs_clearing'] = [];
        $settings['channels'] = [];

        $logsTypes = [
            \Ess\M2ePro\Model\Log\Clearing::LOG_LISTINGS,
            \Ess\M2ePro\Model\Log\Clearing::LOG_SYNCHRONIZATIONS,
            \Ess\M2ePro\Model\Log\Clearing::LOG_ORDERS,
        ];
        foreach ($logsTypes as $logType) {
            $settings['logs_clearing'][$logType] = [
                'mode' => (bool)$config->getGroupValue('/logs/clearing/' . $logType . '/', 'mode'),
                'days' => (int)$config->getGroupValue('/logs/clearing/' . $logType . '/', 'days'),
            ];
        }

        foreach ($this->helperComponent->getComponents() as $component) {
            $settings['channels'][$component]['enabled'] = (bool)$config->getGroupValue(
                '/component/' . $component . '/',
                'mode'
            );
        }

        $settings['config'] = $config->getAllConfigData();

        $data['settings'] = $settings;
    }

    // ---------------------------------------

    /**
     * @param array $data
     *
     * @return void
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    private function appendExtensionMarketplacesInfo(array &$data): void
    {
        $data['marketplaces'] = [];

        $collection = $this->activeRecordFactory->getObject('Marketplace')->getCollection();
        $collection->addFieldToFilter('status', \Ess\M2ePro\Model\Marketplace::STATUS_ENABLE);

        /** @var \Ess\M2ePro\Model\Marketplace $item */
        foreach ($collection->getItems() as $item) {
            $data['marketplaces'][$item->getComponentMode()][$item->getNativeId()] = $item->getTitle();
        }

        $amazonMarketplaces = $this->parentFactory->getObject(HelperAmazon::NICK, 'Marketplace')->getCollection();
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

    /**
     * @param string $component
     * @param int|null $marketplaceId
     *
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Zend_Db_Select_Exception
     */
    protected function getUniqueProductsByMarketplace(string $component, ?int $marketplaceId): array
    {
        $databaseHelper = $this->helperDatabaseStructure;
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
        $products->getSelect()->reset(\Magento\Framework\DB\Select::COLUMNS);
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
        $options->getSelect()->reset(\Magento\Framework\DB\Select::COLUMNS);
        $options->getSelect()->columns(['product_id']);

        $unionStmt = $this->resource
            ->getConnection()
            ->select()
            ->union(
                [
                    $products->getSelect(),
                    $options->getSelect(),
                ]
            )
            ->query();

        $ids = [];
        while ($productId = $unionStmt->fetchColumn()) {
            $ids[] = (int)$productId;
        }

        return $ids;
    }

    /**
     * @param array $ebayIds
     * @param array $amazonIds
     *
     * @return array
     */
    protected function getFilledProductsByMarketplaceInfo(array $ebayIds, array $amazonIds): array
    {
        $bothIds = array_intersect($ebayIds, $amazonIds);
        $onlyEbayIds = array_diff($ebayIds, $amazonIds);
        $onlyAmazonIds = array_diff($amazonIds, $ebayIds);

        return [
            'on-ebay' => count($ebayIds),
            'on-amazon' => count($amazonIds),
            'only-ebay' => count($onlyEbayIds),
            'only-amazon' => count($onlyAmazonIds),
            'both' => count($bothIds),
        ];
    }

    // ---------------------------------------

    /**
     * @param array $data
     *
     * @return void
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    private function appendExtensionAccountsInfo(array &$data): void
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

                if ($childItem->isRepricing()) {
                    $tempInfoRepricer = $childItem->getRepricing();
                    $tempInfo['repricer']['regular_price_mode'] = $tempInfoRepricer['regular_price_mode'];
                    $tempInfo['repricer']['min_price_mode'] = $tempInfoRepricer['min_price_mode'];
                    $tempInfo['repricer']['max_price_mode'] = $tempInfoRepricer['max_price_mode'];
                    $tempInfo['repricer']['disable_mode'] = $tempInfoRepricer['disable_mode'];
                }
            }

            $tempInfo['other_listings_synch'] = $childItem->isOtherListingsSynchronizationEnabled();

            $data['accounts'][$item->getComponentMode()][$item->getTitle()] = $tempInfo;
        }
    }

    /**
     * @param array $data
     *
     * @return void
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    private function appendExtensionListingsInfo(array &$data): void
    {
        $queryStmt = $this->resource->getConnection()
                                    ->select()
                                    ->from(
                                        $this->helperDatabaseStructure->getTableNameWithPrefix('m2epro_listing'),
                                        [
                                            'count' => new \Zend_Db_Expr('COUNT(*)'),
                                            'component' => 'component_mode',
                                            'marketplace_id' => 'marketplace_id',
                                            'account_id' => 'account_id',
                                            'store_id' => 'store_id',
                                        ]
                                    )
                                    ->group([
                                        'component_mode',
                                        'marketplace_id',
                                        'account_id',
                                        'store_id',
                                    ])
                                    ->query();

        $data['listings']['total'] = 0;

        $availableComponents = $this->helperComponent->getComponents();
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

            $storePath = (string)$this->helperMagentoStore->getStorePath($row['store_id']);

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

    /**
     * @param array $data
     *
     * @return void
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    private function appendExtensionListingsProductsInfo(array &$data): void
    {
        $structureHelper = $this->helperDatabaseStructure;

        $m2eproListing = $structureHelper->getTableNameWithPrefix('m2epro_listing');
        $m2eproListingProduct = $structureHelper->getTableNameWithPrefix('m2epro_listing_product');

        $sql = "SELECT
                    l.component_mode                                                         AS component,
                    l.marketplace_id                                                         AS marketplace_id,
                    l.account_id                                                             AS account_id,
                    (SELECT COUNT(*) FROM `{$m2eproListingProduct}` WHERE listing_id = l.id) AS products_count
                FROM `{$m2eproListing}` AS `l`;";

        $queryStmt = $this->resource->getConnection()->query($sql);

        $productTypes = [
            \Ess\M2ePro\Model\Magento\Product::TYPE_SIMPLE_ORIGIN,
            \Ess\M2ePro\Model\Magento\Product::TYPE_CONFIGURABLE_ORIGIN,
            \Ess\M2ePro\Model\Magento\Product::TYPE_BUNDLE_ORIGIN,
            \Ess\M2ePro\Model\Magento\Product::TYPE_GROUPED_ORIGIN,
            \Ess\M2ePro\Model\Magento\Product::TYPE_DOWNLOADABLE_ORIGIN,
            \Ess\M2ePro\Model\Magento\Product::TYPE_VIRTUAL_ORIGIN,
        ];

        $data['listings_products']['total'] = 0;

        foreach ($this->helperComponent->getComponents() as $componentName) {
            $data['listings_products'][$componentName]['total'] = 0;

            foreach ($productTypes as $productType) {
                $select = $this->resource->getConnection()
                                         ->select()
                                         ->from(
                                             [
                                                 'lp' => $structureHelper->getTableNameWithPrefix(
                                                     'm2epro_listing_product'
                                                 ),
                                             ],
                                             ['count(*)']
                                         )
                                         ->where('component_mode = ?', $componentName)
                                         ->joinLeft(
                                             [
                                                 'cpe' => $structureHelper->getTableNameWithPrefix(
                                                     'catalog_product_entity'
                                                 ),
                                             ],
                                             'lp.product_id = cpe.entity_id',
                                             []
                                         )
                                         ->where('type_id = ?', $productType);

                if ($componentName === HelperAmazon::NICK || $componentName === HelperWalmart::NICK) {
                    $tableComponentLp = $structureHelper->getTableNameWithPrefix(
                        'm2epro_' . $componentName . '_listing_product'
                    );

                    $select->joinLeft(
                        ['clp' => $tableComponentLp],
                        'lp.id = clp.listing_product_id',
                        []
                    )
                           ->where('variation_parent_id IS NULL');
                }

                $data['listings_products'][$componentName]['products']['type'][$productType] = [
                    'amount' => (int)$this->resource->getConnection()->fetchOne($select),
                ];
            }
        }

        foreach ($productTypes as $productType) {
            $amount = 0;
            foreach ($this->helperComponent->getComponents() as $component) {
                $amount += $data['listings_products'][$component]['products']['type'][$productType]['amount'];
            }

            $data['listings_products']['products']['type'][$productType] = ['amount' => $amount];
        }

        while ($row = $queryStmt->fetch()) {
            if (!in_array($row['component'], $this->helperComponent->getComponents())) {
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

    /**
     * @param array $data
     *
     * @return void
     * @throws \Exception
     */
    private function appendExtensionListingProductInstructionInfo(array &$data): void
    {
        $instructionTypeTiming = $this->registryManager
            ->getValueFromJson(InstructionType::REGISTRY_KEY_DATA);

        $currentDateTime = \Ess\M2ePro\Helper\Date::createCurrentGmt();
        $currentDate = $currentDateTime->format('Y-m-d');
        $currentHour = $currentDateTime->format('H-00');

        $lastHourTiming = [];

        // Cut the last hour from the instruction type array to prevent overlapping time ranges next time
        // Save the last hour to a new instruction type array
        if (isset($instructionTypeTiming[$currentDate][$currentHour])) {
            $lastHourTiming[$currentDate][$currentHour] = $instructionTypeTiming[$currentDate][$currentHour];

            unset($instructionTypeTiming[$currentDate][$currentHour]);
        }

        $data['listing_product_instruction_type_statistic'] = $instructionTypeTiming;

        $this->registryManager
            ->setValue(InstructionType::REGISTRY_KEY_DATA, $lastHourTiming);
    }

    /**
     * @param array $data
     *
     * @return void
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    private function appendExtensionListingsOtherInfo(array &$data): void
    {
        $queryStmt = $this->resource->getConnection()
                                    ->select()
                                    ->from(
                                        $this->helperDatabaseStructure->getTableNameWithPrefix('m2epro_listing_other'),
                                        [
                                            'count' => new \Zend_Db_Expr('COUNT(*)'),
                                            'component' => 'component_mode',
                                            'marketplace_id' => 'marketplace_id',
                                            'account_id' => 'account_id',
                                        ]
                                    )
                                    ->group([
                                        'component_mode',
                                        'marketplace_id',
                                        'account_id',
                                    ])
                                    ->query();

        $data['listings_other']['total'] = 0;

        $availableComponents = $this->helperComponent->getComponents();
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

    /**
     * @param array $data
     *
     * @return void
     */
    private function appendExtensionPoliciesInfo(array &$data): void
    {
        $this->_appendComponentPolicyInfo('selling_format', 'amazon', $data);
        $this->_appendComponentPolicyInfo('synchronization', 'amazon', $data);
        $this->_appendComponentPolicyInfo('description', 'amazon', $data);
        $this->_appendComponentPolicyInfo('product_tax_code', 'amazon', $data);
        $this->_appendComponentPolicyInfo('shipping', 'amazon', $data);

        $this->_appendComponentPolicyInfo('selling_format', 'ebay', $data);
        $this->_appendComponentPolicyInfo('synchronization', 'ebay', $data);
        $this->_appendComponentPolicyInfo('description', 'ebay', $data);
        $this->_appendComponentPolicyInfo('shipping', 'ebay', $data);
        $this->_appendComponentPolicyInfo('return_policy', 'ebay', $data);
        $this->_appendComponentPolicyInfo('category', 'ebay', $data);
        $this->_appendComponentPolicyInfo('store_category', 'ebay', $data);

        $this->_appendComponentPolicyInfo('selling_format', 'walmart', $data);
        $this->_appendComponentPolicyInfo('synchronization', 'walmart', $data);
        $this->_appendComponentPolicyInfo('description', 'walmart', $data);
        $this->_appendComponentPolicyInfo('category', 'walmart', $data);
    }

    /**
     * @param array $data
     *
     * @return void
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    private function appendExtensionOrdersInfo(array &$data): void
    {
        $queryStmt = $this->resource->getConnection()
                                    ->select()
                                    ->from(
                                        $this->helperDatabaseStructure->getTableNameWithPrefix('m2epro_order'),
                                        [
                                            'count' => new \Zend_Db_Expr('COUNT(*)'),
                                            'component' => 'component_mode',
                                            'marketplace_id' => 'marketplace_id',
                                            'account_id' => 'account_id',
                                        ]
                                    )
                                    ->group([
                                        'component_mode',
                                        'marketplace_id',
                                        'account_id',
                                    ])
                                    ->query();

        $data['orders']['total'] = 0;

        $helper = $this->helperComponent;

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

            $account = $this->parentFactory->getObjectLoaded(
                $row['component'],
                'Account',
                $row['account_id'],
                null,
                false
            );
            $accountTitle = $account === null ? 'account_id_' . $row['account_id'] : $account->getTitle();

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
                                     $this->helperDatabaseStructure->getTableNameWithPrefix('m2epro_ebay_order'),
                                     ['count' => new \Zend_Db_Expr('COUNT(*)')]
                                 )
                                 ->where('checkout_status = ?', \Ess\M2ePro\Model\Ebay\Order::CHECKOUT_STATUS_COMPLETED)
                                 ->query()
                                 ->fetchColumn();

        $data['orders']['ebay']['types']['checkout'] = (int)$result;

        $result = $this->resource->getConnection()
                                 ->select()
                                 ->from(
                                     $this->helperDatabaseStructure->getTableNameWithPrefix('m2epro_ebay_order'),
                                     ['count' => new \Zend_Db_Expr('COUNT(*)')]
                                 )
                                 ->where('shipping_status = ?', \Ess\M2ePro\Model\Ebay\Order::SHIPPING_STATUS_COMPLETED)
                                 ->query()
                                 ->fetchColumn();

        $data['orders']['ebay']['types']['shipped'] = (int)$result;

        $result = $this->resource->getConnection()
                                 ->select()
                                 ->from(
                                     $this->helperDatabaseStructure->getTableNameWithPrefix('m2epro_ebay_order'),
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
                                        $this->helperDatabaseStructure->getTableNameWithPrefix('m2epro_amazon_order'),
                                        [
                                            'count' => new \Zend_Db_Expr('COUNT(*)'),
                                            'status' => 'status',
                                        ]
                                    )
                                    ->group(['status'])
                                    ->query();

        $statuses = [
            \Ess\M2ePro\Model\Amazon\Order::STATUS_PENDING => 'pending',
            \Ess\M2ePro\Model\Amazon\Order::STATUS_UNSHIPPED => 'unshipped',
            \Ess\M2ePro\Model\Amazon\Order::STATUS_SHIPPED_PARTIALLY => 'shipped_partially',
            \Ess\M2ePro\Model\Amazon\Order::STATUS_SHIPPED => 'shipped',
            \Ess\M2ePro\Model\Amazon\Order::STATUS_UNFULFILLABLE => 'unfulfillable',
            \Ess\M2ePro\Model\Amazon\Order::STATUS_CANCELED => 'canceled',
            \Ess\M2ePro\Model\Amazon\Order::STATUS_INVOICE_UNCONFIRMED => 'invoice_uncorfirmed',
            \Ess\M2ePro\Model\Amazon\Order::STATUS_CANCELLATION_REQUESTED => 'unshipped_cancellation_requested',
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

    /**
     * @param array $data
     *
     * @return void
     */
    private function appendExtensionLogsInfo(array &$data): void
    {
        $data['logs']['total'] = 0;

        foreach ($this->helperComponent->getComponents() as $nick) {
            $data['logs'][$nick]['total'] = 0;
        }

        $data = $this->_appendLogsInfoByType('listings', 'm2epro_listing_log', $data);
        $data = $this->_appendLogsInfoByType('synchronization', 'm2epro_synchronization_log', $data);
        $data = $this->_appendLogsInfoByType('orders', 'm2epro_order_log', $data);
    }

    // ---------------------------------------

    /**
     * @param string $type
     * @param string $tableName
     * @param array $data
     *
     * @return array
     */
    private function _appendLogsInfoByType(string $type, string $tableName, array $data): array
    {
        $queryStmt = $this->resource->getConnection()
                                    ->select()
                                    ->from(
                                        $this->helperDatabaseStructure->getTableNameWithPrefix($tableName),
                                        [
                                            'count' => new \Zend_Db_Expr('COUNT(*)'),
                                            'component' => 'component_mode',
                                        ]
                                    )
                                    ->group('component_mode')
                                    ->query();

        $data['logs']['types'][$type] = 0;

        $availableComponents = $this->helperComponent->getComponents();
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

    /**
     * @param string $template
     * @param string $component
     * @param array $data
     *
     * @return void
     */
    private function _appendComponentPolicyInfo(string $template, string $component, array &$data): void
    {
        $structureHelper = $this->helperDatabaseStructure;
        $tableName = $structureHelper->getTableNameWithPrefix('m2epro_' . $component . '_template_' . $template);

        $queryStmt = $this->resource->getConnection()
                                    ->select()
                                    ->from($tableName, ['count' => new \Zend_Db_Expr('COUNT(*)')])
                                    ->query();

        $data['policies'][$component][$template]['count'] = (int)$queryStmt->fetchColumn();

        if ($component === HelperEbay::NICK && !in_array($template, ['category', 'store_category'])) {
            $queryStmt = $this->resource->getConnection()
                                        ->select()
                                        ->from(
                                            $structureHelper->getTableNameWithPrefix('m2epro_ebay_listing_product'),
                                            ['count(*)']
                                        )
                                        ->where(
                                            "template_{$template}_mode != ?",
                                            \Ess\M2ePro\Model\Ebay\Template\Manager::MODE_PARENT
                                        )
                                        ->query();

            $data['policies'][$component][$template]['is_custom_for_listing_products'] = (int)$queryStmt->fetchColumn();
        }
    }

    // ---------------------------------------
}

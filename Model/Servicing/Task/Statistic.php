<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Servicing\Task;

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

    protected $synchronizationConfig;

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
        \Magento\Eav\Model\Config $config,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Ess\M2ePro\Model\Config\Manager\Synchronization $synchronizationConfig,
        \Ess\M2ePro\Model\Config\Manager\Cache $cacheConfig,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\App\ResourceConnection $resource,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory
    )
    {
        $this->transactionCollectionFactory = $transactionCollectionFactory;
        $this->creditmemoCollectionFactory = $creditmemoCollectionFactory;
        $this->shipmentCollectionFactory = $shipmentCollectionFactory;
        $this->invoiceCollectionFactory = $invoiceCollectionFactory;
        $this->categoryFactory = $categoryFactory;
        $this->attributeSetFactory = $attributeSetFactory;
        $this->productFactory = $productFactory;
        $this->attributeCollection = $attributeCollection;
        $this->moduleList = $moduleList;
        $this->synchronizationConfig = $synchronizationConfig;
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

        if (is_null($lastRun) ||
            $this->getHelper('Data')->getCurrentGmtDate(true) > strtotime($lastRun) + self::RUN_INTERVAL) {

            $cacheConfig->setGroupValue('/servicing/statistic/', 'last_run',
                                        $this->getHelper('Data')->getCurrentGmtDate());

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
        $requestData['statistics'] = array();

        try {

            $requestData['statistics']['magento'] = $this->getMagentoRequestPart();
            $requestData['statistics']['extension'] = $this->getExtensionRequestPart();

        } catch (\Exception $e) {
            return $requestData;
        }

        return $requestData;
    }

    public function processResponseData(array $data) {}

    //########################################

    private function getMagentoRequestPart()
    {
        $data = array();

        $data['info']['edition'] = $this->getHelper('Magento')->getEditionName();
        $data['info']['version'] = $this->getHelper('Magento')->getVersion();

        $data['settings']['compilation'] = defined('COMPILER_INCLUDE_PATH');
        //$data['settings']['cache_backend'] = $this->getHelper('Client\Cache')->getBackend();
        $data['settings']['secret_key'] = $this->getHelper('Magento')->isSecretKeyToUrl();

        $data = $this->appendModulesInfo($data);
        $data = $this->appendStoresInfo($data);

        $data = $this->appendAttributesInfo($data);
        $data = $this->appendProductsInfo($data);
        $data = $this->appendOrdersInfo($data);

        return $data;
    }

    // ---------------------------------------

    private function appendModulesInfo($data)
    {
        $data['modules'] = array();

        foreach ($this->moduleList->getAll() as $module => $moduleData) {

            $data['modules'][$module] = array(
                'name'    => $module,
                'version' => isset($moduleData['version']) ? $moduleData['version'] : null,
                'status'  => (isset($moduleData['active']) && $moduleData['active'] === 'true')
            );
        }

        return $data;
    }

    private function appendStoresInfo($data)
    {
        $data['stores'] = array();

        foreach ($this->storeManager->getWebsites() as $website) {
            foreach ($website->getGroups() as $group) {
                foreach ($group->getStores() as $store) {

                    $data['stores'][$website->getName()][$group->getName()][] = $store->getName();
                }
            }
        }

        return $data;
    }

    private function appendAttributesInfo($data)
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

        return $data;
    }

    private function appendProductsInfo($data)
    {
        // Count of Products
        $queryStmt = $this->resource->getConnection()
              ->select()
              ->from($this->resource->getTableName('catalog_product_entity'),
                     array(
                         'count' => new \Zend_Db_Expr('COUNT(*)'),
                         'type'  => 'type_id'
                     ))
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
              ->from(array('stock_item' => $this->resource->getTableName('cataloginventory_stock_item')),
                     array(
                         'min_qty'     => new \Zend_Db_Expr('MIN(stock_item.qty)'),
                         'max_qty'     => new \Zend_Db_Expr('MAX(stock_item.qty)'),
                         'avg_qty'     => new \Zend_Db_Expr('AVG(stock_item.qty)'),
                         'count'       => new \Zend_Db_Expr('COUNT(*)'),
                         'is_in_stock' => 'stock_item.is_in_stock'
                     ))
              ->joinLeft(
                  array('catalog_product' => $this->resource->getTableName('catalog_product_entity')),
                  'stock_item.product_id = catalog_product.entity_id',
                  array()
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
              ->from($this->resource->getTableName('catalog_product_index_price'),
                     array(
                         'min_price' => new \Zend_Db_Expr('MIN(price)'),
                         'max_price' => new \Zend_Db_Expr('MAX(price)'),
                         'avg_price' => new \Zend_Db_Expr('AVG(price)')
                     ))
              ->where('website_id = ?', $this->storeManager->getWebsite(true)->getId())
              ->query()
              ->fetch();

        $data['products']['price']['min'] = round($result['min_price'], 2);
        $data['products']['price']['max'] = round($result['max_price'], 2);
        $data['products']['price']['avg'] = round($result['avg_price'], 2);
        // ---------------------------------------

        return $data;
    }

    private function appendOrdersInfo($data)
    {
        // Count of Orders
        $queryStmt = $this->resource->getConnection()
              ->select()
              ->from($this->resource->getTableName('sales_order'),
                     array(
                         'count'  => new \Zend_Db_Expr('COUNT(*)'),
                         'status' => 'status'
                     ))
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

        return $data;
    }

    //########################################

    private function getExtensionRequestPart()
    {
        $data = array();

        $data['info']['version'] = $this->getHelper('Module')->getVersion();

        $data = $this->appendTablesInfo($data);
        $data = $this->appendSettingsInfo($data);

        $data = $this->appendMarketplacesInfo($data);
        $data = $this->appendAccountsInfo($data);

        $data = $this->appendListingsInfo($data);
        $data = $this->appendListingsProductsInfo($data);
        $data = $this->appendListingsOtherInfo($data);

        $data = $this->appendPoliciesInfo($data);
        $data = $this->appendExtensionOrdersInfo($data);

        $data = $this->appendLogsInfo($data);

        return $data;
    }

    // ---------------------------------------

    private function appendTablesInfo($data)
    {
        $helper = $this->getHelper('Module\Database\Structure');
        $data['info']['tables'] = array();

        foreach ($helper->getMySqlTables() as $tableName) {

            $data['info']['tables'][$tableName] = array(
                'size'   => $helper->getDataLength($tableName),
                'amount' => $helper->getCountOfRecords($tableName),
            );
        }

        return $data;
    }

    private function appendSettingsInfo($data)
    {
        $config = $this->getHelper('Module')->getConfig();

        $data['settings']['track_direct'] = $this->synchronizationConfig->getGroupValue(
            '/global/magento_products/inspector/','mode'
        );
        $data['settings']['manage_stock_backorders'] = false;

        if ($config->getGroupValue('/product/force_qty/','mode')) {
            $data['settings']['manage_stock_backorders'] = $config->getGroupValue('/product/force_qty/','value');
        }

        $data['settings']['channels']['default'] = $this->getHelper('View\Amazon\Component')->getDefaultComponent();

        foreach ($this->getHelper('Component')->getComponents() as $componentNick) {

            $tempInfo = array();

            $tempInfo['enabled'] = $config->getGroupValue('/component/'.$componentNick.'/', 'mode');

            if ($componentNick == \Ess\M2ePro\Helper\Component\Ebay::NICK) {
                $tempInfo['mode'] = $config->getGroupValue('/view/ebay/', 'mode');
            }

            $data['settings']['channels'][$componentNick] = $tempInfo;
        }

        return $data;
    }

    private function appendMarketplacesInfo($data)
    {
        $data['marketplaces'] = array();

        $collection = $this->parentFactory->getObject(\Ess\M2ePro\Helper\Component\Ebay::NICK, 'Marketplace')
            ->getCollection();
        $collection->addFieldToFilter('status', \Ess\M2ePro\Model\Marketplace::STATUS_ENABLE);

        /** @var \Ess\M2ePro\Model\Marketplace $item */
        foreach ($collection->getItems() as $item) {

            $data['marketplaces'][$item->getComponentMode()][$item->getNativeId()] = $item->getTitle();
        }

        return $data;
    }

    private function appendAccountsInfo($data)
    {
        $data['accounts'] = array();

        $collection = $this->activeRecordFactory->getObject('Account')->getCollection();

        /** @var \Ess\M2ePro\Model\Account $item */
        foreach ($collection->getItems() as $item) {

            $tempInfo = array();
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

        return $data;
    }

    private function appendListingsInfo($data)
    {
        $queryStmt = $this->resource->getConnection()
              ->select()
              ->from($this->resource->getTableName('m2epro_listing'),
                     array(
                         'count'          => new \Zend_Db_Expr('COUNT(*)'),
                         'component'      => 'component_mode',
                         'marketplace_id' => 'marketplace_id',
                         'account_id'     => 'account_id',
                         'store_id'       => 'store_id'
                     ))
              ->group(array(
                          'component_mode',
                          'marketplace_id',
                          'account_id',
                          'store_id'
                      ))
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

        return $data;
    }

    private function appendListingsProductsInfo($data)
    {
        $queryStmt = $this->resource->getConnection()
              ->select()
              ->from($this->resource->getTableName('m2epro_listing'),
                     array(
                         'component'      => 'component_mode',
                         'marketplace_id' => 'marketplace_id',
                         'account_id'     => 'account_id',
                         'products_count' => 'products_total_count'
                     ))
              ->group(array(
                          'component_mode',
                          'marketplace_id',
                          'account_id'
                      ))
              ->query();

        $data['listings_products']['total'] = 0;

        $availableComponents = $this->getHelper('Component')->getComponents();
        foreach ($availableComponents as $nick) {
            $data['listings_products'][$nick]['total'] = 0;
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

        // TODO NEXT (append information by product types [count of simple, configurable, etc])

        return $data;
    }

    private function appendListingsOtherInfo($data)
    {
        $queryStmt = $this->resource->getConnection()
              ->select()
              ->from($this->resource->getTableName('m2epro_listing_other'),
                     array(
                         'count'          => new \Zend_Db_Expr('COUNT(*)'),
                         'component'      => 'component_mode',
                         'marketplace_id' => 'marketplace_id',
                         'account_id'     => 'account_id',
                     ))
              ->group(array(
                          'component_mode',
                          'marketplace_id',
                          'account_id'
                      ))
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

        return $data;
    }

    private function appendPoliciesInfo($data)
    {
        $data = $this->_appendGeneralPolicyInfoByType('selling_format', 'm2epro_template_selling_format', $data);
        $data = $this->_appendGeneralPolicyInfoByType('synchronization', 'm2epro_template_synchronization', $data);
        $data = $this->_appendGeneralPolicyInfoByType('description', 'm2epro_template_description', $data);

        $data = $this->_appendEbayPolicyInfoByType('payment', 'm2epro_ebay_template_payment', $data);
        $data = $this->_appendEbayPolicyInfoByType('shipping', 'm2epro_ebay_template_shipping', $data);
        $data = $this->_appendEbayPolicyInfoByType('return', 'm2epro_ebay_template_return_policy', $data);

        return $data;
    }

    private function appendExtensionOrdersInfo($data)
    {
        $queryStmt = $this->resource->getConnection()
              ->select()
              ->from($this->resource->getTableName('m2epro_order'),
                     array(
                         'count'          => new \Zend_Db_Expr('COUNT(*)'),
                         'component'      => 'component_mode',
                         'marketplace_id' => 'marketplace_id',
                         'account_id'     => 'account_id',
                     ))
              ->group(array(
                          'component_mode',
                          'marketplace_id',
                          'account_id'
                      ))
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
               ->from($this->resource->getTableName('m2epro_ebay_order'),
                      array('count' => new \Zend_Db_Expr('COUNT(*)')))
               ->where('checkout_status = ?', \Ess\M2ePro\Model\Ebay\Order::CHECKOUT_STATUS_COMPLETED)
               ->query()
               ->fetchColumn();

        $data['orders']['ebay']['types']['checkout'] = (int)$result;

        $result = $this->resource->getConnection()
               ->select()
               ->from($this->resource->getTableName('m2epro_ebay_order'),
                      array('count' => new \Zend_Db_Expr('COUNT(*)')))
               ->where('shipping_status = ?', \Ess\M2ePro\Model\Ebay\Order::SHIPPING_STATUS_COMPLETED)
               ->query()
               ->fetchColumn();

        $data['orders']['ebay']['types']['shipped'] = (int)$result;

        $result = $this->resource->getConnection()
              ->select()
              ->from($this->resource->getTableName('m2epro_ebay_order'),
                     array('count' => new \Zend_Db_Expr('COUNT(*)')))
              ->where('payment_status = ?', \Ess\M2ePro\Model\Ebay\Order::PAYMENT_STATUS_COMPLETED)
              ->query()
              ->fetchColumn();

        $data['orders']['ebay']['types']['paid'] = (int)$result;
        // ---------------------------------------

        // Orders types Amazon
        $queryStmt = $this->resource->getConnection()
               ->select()
               ->from($this->resource->getTableName('m2epro_amazon_order'),
                      array(
                          'count'  => new \Zend_Db_Expr('COUNT(*)'),
                          'status' => 'status'
                      ))
               ->group(array('status'))
               ->query();

        $statuses = array(
            \Ess\M2ePro\Model\Amazon\Order::STATUS_PENDING             => 'pending',
            \Ess\M2ePro\Model\Amazon\Order::STATUS_UNSHIPPED           => 'unshipped',
            \Ess\M2ePro\Model\Amazon\Order::STATUS_SHIPPED_PARTIALLY   => 'shipped_partially',
            \Ess\M2ePro\Model\Amazon\Order::STATUS_SHIPPED             => 'shipped',
            \Ess\M2ePro\Model\Amazon\Order::STATUS_UNFULFILLABLE       => 'unfulfillable',
            \Ess\M2ePro\Model\Amazon\Order::STATUS_CANCELED            => 'canceled',
            \Ess\M2ePro\Model\Amazon\Order::STATUS_INVOICE_UNCONFIRMED => 'invoice_uncorfirmed'
        );

        while ($row = $queryStmt->fetch()) {

            $status = $statuses[(int)$row['status']];

            if (!isset($data['orders']['amazon']['types'][$status])) {
                $data['orders']['amazon']['types'][$status] = 0;
            }

            $data['orders']['amazon']['types'][$status] += (int)$row['count'];
        }
        // ---------------------------------------

        return $data;
    }

    private function appendLogsInfo($data)
    {
        $data['logs']['total'] = 0;

        foreach ($this->getHelper('Component')->getComponents() as $nick) {
            $data['logs'][$nick]['total'] = 0;
        }

        $data = $this->_appendLogsInfoByType('listings', 'm2epro_listing_log', $data);
        $data = $this->_appendLogsInfoByType('synchronization', 'm2epro_synchronization_log', $data);
        $data = $this->_appendLogsInfoByType('orders', 'm2epro_order_log', $data);
        $data = $this->_appendLogsInfoByType('other_listings', 'm2epro_listing_other_log', $data);

        return $data;
    }

    //########################################

    private function _appendLogsInfoByType($type, $tableName, $data)
    {
        $queryStmt = $this->resource->getConnection()
              ->select()
              ->from($this->resource->getTableName($tableName),
                     array(
                         'count'     => new \Zend_Db_Expr('COUNT(*)'),
                         'component' => 'component_mode'
                     ))
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
              ->from($this->resource->getTableName($tableName),
                     array(
                         'count'     => new \Zend_Db_Expr('COUNT(*)'),
                         'component' => 'component_mode'
                     ))
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
              ->from($this->resource->getTableName($tableName),
                     array(
                         'count'     => new \Zend_Db_Expr('COUNT(*)')
                     ))
              ->query();

        $data['policies']['ebay'][$type] = (int)$queryStmt->fetchColumn();

        return $data;
    }

    //########################################
}
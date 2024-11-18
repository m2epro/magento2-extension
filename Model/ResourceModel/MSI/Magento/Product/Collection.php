<?php

namespace Ess\M2ePro\Model\ResourceModel\MSI\Magento\Product;

use Magento\InventorySalesApi\Model\StockByWebsiteIdResolverInterface;
use Magento\InventoryIndexer\Model\StockIndexTableNameResolverInterface;
use Magento\InventoryCatalogApi\Api\DefaultStockProviderInterface;

class Collection extends \Ess\M2ePro\Model\ResourceModel\Magento\Product\Collection
{
    /** @var \Ess\M2ePro\Helper\Magento\Stock */
    private $stockHelper;
    /** @var StockIndexTableNameResolverInterface */
    private $indexNameResolver;
    /** @var StockByWebsiteIdResolverInterface */
    private $stockResolver;
    /** @var DefaultStockProviderInterface */
    private $defaultStockResolver;

    public function __construct(
        \Ess\M2ePro\Helper\Magento\Stock $stockHelper,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Magento\Framework\Data\Collection\EntityFactory $entityFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Eav\Model\Config $eavConfig,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Eav\Model\EntityFactory $eavEntityFactory,
        \Magento\Catalog\Model\ResourceModel\Helper $resourceHelper,
        \Magento\Framework\Validator\UniversalFactory $universalFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Module\Manager $moduleManager,
        \Magento\Catalog\Model\Indexer\Product\Flat\State $catalogProductFlatState,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Catalog\Model\Product\OptionFactory $productOptionFactory,
        \Magento\Catalog\Model\ResourceModel\Url $catalogUrl,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Stdlib\DateTime $dateTime,
        \Magento\Customer\Api\GroupManagementInterface $groupManagement,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\DB\Adapter\AdapterInterface $connection = null
    ) {
        parent::__construct(
            $helperFactory,
            $modelFactory,
            $activeRecordFactory,
            $entityFactory,
            $logger,
            $fetchStrategy,
            $eventManager,
            $eavConfig,
            $resource,
            $eavEntityFactory,
            $resourceHelper,
            $universalFactory,
            $storeManager,
            $moduleManager,
            $catalogProductFlatState,
            $scopeConfig,
            $productOptionFactory,
            $catalogUrl,
            $localeDate,
            $customerSession,
            $dateTime,
            $groupManagement,
            $objectManager,
            $connection
        );

        $this->stockHelper = $stockHelper;
        $this->indexNameResolver = $objectManager->get(StockIndexTableNameResolverInterface::class);
        $this->stockResolver = $objectManager->get(StockByWebsiteIdResolverInterface::class);
        $this->defaultStockResolver = $objectManager->get(DefaultStockProviderInterface::class);
    }

    public function joinStockItem()
    {
        /** @var \Ess\M2ePro\Helper\Magento\Store $storeHelper */
        $storeHelper = $this->helperFactory->getObject('Magento\Store');

        $website = $this->getStoreId() === \Magento\Store\Model\Store::DEFAULT_STORE_ID
            ? $storeHelper->getDefaultWebsite()
            : $storeHelper->getWebsite($this->getStoreId());

        $defaultStockId = (int)$this->defaultStockResolver->getId();
        $websiteStockId = (int)$this->stockResolver->execute($website->getId())->getStockId();

        if ($defaultStockId === $websiteStockId) {
            $this->joinOnlyDefaultInventoryStockItem(
                $this->indexNameResolver->execute($defaultStockId)
            );
        } else {
            $this->joinDefaultInventoryStockItemWithWebsiteInventoryStock(
                $this->indexNameResolver->execute($defaultStockId),
                $this->indexNameResolver->execute($websiteStockId)
            );
        }
    }

    public function addAttributeToFilter($attribute, $condition = null, $joinType = 'inner')
    {
        if ($attribute == 'is_in_stock') {
            $this->getSelect()->where($this->getCheckSqlForStock() . ' = ?', $condition);

            return $this;
        }

        if ($attribute == 'qty') {
            if (isset($condition['from'])) {
                $this->getSelect()->where($this->getCheckSqlForQty() . ' >= ?', $condition['from']);
            }

            if (isset($condition['to'])) {
                $this->getSelect()->where($this->getCheckSqlForQty() . ' <= ?', $condition['to']);
            }

            return $this;
        }

        return parent::addAttributeToFilter($attribute, $condition, $joinType);
    }

    public function addAttributeToSort($attribute, $dir = self::SORT_ORDER_ASC)
    {
        if ($attribute == 'qty') {
            return $this->getSelect()->order($this->getCheckSqlForQty() . ' ' . $dir);
        }

        return parent::addAttributeToSort($attribute, $dir);
    }

    private function joinOnlyDefaultInventoryStockItem(string $defaultInventoryStockTableName)
    {
        $this->joinTable(
            ['it_def' => $defaultInventoryStockTableName],
            'sku=sku',
            [
                'def_quantity' => 'quantity',
                'def_is_in_stock' => 'is_salable',
            ],
            [
                'stock_id' => $this->stockHelper->getStockId($this->getStoreId()),
                'website_id' => $this->stockHelper->getWebsiteId($this->getStoreId()),
            ],
            'left'
        );

        $this->getSelect()->columns([
            'qty' => 'it_def.quantity',
            'is_in_stock' => 'it_def.is_salable',
        ]);
    }

    private function joinDefaultInventoryStockItemWithWebsiteInventoryStock(
        string $defaultInventoryStockTableName,
        string $websiteInventoryStockTableName
    ) {
        $this->joinTable(
            ['it_def' => $defaultInventoryStockTableName],
            'sku=sku',
            [
                'def_quantity' => 'quantity',
                'def_is_in_stock' => 'is_salable',
            ],
            [
                'stock_id' => $this->stockHelper->getStockId($this->getStoreId()),
                'website_id' => $this->stockHelper->getWebsiteId($this->getStoreId()),
            ],
            'left'
        );

        $this->joinTable(
            ['it' => $websiteInventoryStockTableName],
            'sku=sku',
            [
                'stock_quantity' => 'quantity',
                'stock_is_in_stock' => 'is_salable',
            ],
            null,
            'left'
        );

        $this->getSelect()->columns([
            'qty' => $this->getCheckSqlForQty(),
            'is_in_stock' => $this->getCheckSqlForStock(),
        ]);
    }

    private function getCheckSqlForQty(): \Zend_Db_Expr
    {
        return $this->getConnection()->getCheckSql(
            'it.sku IS NOT NULL',
            'IFNULL(it.quantity, 0)',
            'IFNULL(it_def.quantity, 0)'
        );
    }

    private function getCheckSqlForStock(): \Zend_Db_Expr
    {
        return $this->getConnection()->getCheckSql(
            'it.sku IS NOT NULL',
            'it.is_salable',
            'IFNULL(it_def.is_salable, 0)'
        );
    }
}

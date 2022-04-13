<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\MSI\Magento\Product;

use Magento\InventoryReservationsApi\Model\ReservationInterface;
use Magento\InventorySalesApi\Model\StockByWebsiteIdResolverInterface;
use Magento\InventoryIndexer\Model\StockIndexTableNameResolverInterface;
use Magento\InventoryCatalogApi\Api\DefaultStockProviderInterface;

/**
 * Class \Ess\M2ePro\Model\ResourceModel\MSI\Magento\Product\Collection
 */
class Collection extends \Ess\M2ePro\Model\ResourceModel\Magento\Product\Collection
{
    /** @var StockIndexTableNameResolverInterface */
    protected $indexNameResolver;

    /** @var StockByWebsiteIdResolverInterface */
    protected $stockResolver;

    /** @var DefaultStockProviderInterface */
    protected $defaultStockResolver;

    //########################################

    public function __construct(
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

        $this->indexNameResolver = $objectManager->get(StockIndexTableNameResolverInterface::class);
        $this->stockResolver = $objectManager->get(StockByWebsiteIdResolverInterface::class);
        $this->defaultStockResolver = $objectManager->get(DefaultStockProviderInterface::class);
    }

    //########################################

    public function joinStockItem()
    {
        $website = $this->getStoreId() === \Magento\Store\Model\Store::DEFAULT_STORE_ID
            ? $this->helperFactory->getObject('Magento\Store')->getDefaultWebsite()
            : $this->helperFactory->getObject('Magento\Store')->getWebsite($this->getStoreId());

        $stockId = $this->stockResolver->execute($website->getId())->getStockId();

        $this->joinTable(
            ['it' => $this->indexNameResolver->execute($stockId)],
            'sku=sku',
            [
                'stock_quantity'    => 'quantity',
                'stock_is_in_stock' => 'is_salable'
            ],
            null,
            'left'
        );
        $this->joinTable(
            ['it_def' => $this->indexNameResolver->execute($this->defaultStockResolver->getId())],
            'sku=sku',
            [
                'def_quantity'    => 'quantity',
                'def_is_in_stock' => 'is_salable'
            ],
            null,
            'left'
        );

        $this->getSelect()->columns([
            'qty'         => $this->getCheckSqlForQty(),
            'is_in_stock' => $this->getCheckSqlForStock()
        ]);
    }

    //########################################

    public function getCheckSqlForQty()
    {
        return $this->getConnection()->getCheckSql(
            'it.sku IS NOT NULL',
            'IFNULL(it.quantity, 0)',
            'IFNULL(it_def.quantity, 0)'
        );
    }

    //########################################

    public function getCheckSqlForStock()
    {
        return $this->getConnection()->getCheckSql(
            'it.sku IS NOT NULL',
            'it.is_salable',
            'IFNULL(it_def.is_salable, 0)'
        );
    }

    //########################################

    public function addAttributeToFilter($attribute, $condition = null, $joinType = 'inner')
    {
        if ($attribute == 'is_in_stock') {
            $this->getSelect()->where($this->getCheckSqlForStock().' = ?', $condition);

            return $this;
        }

        if ($attribute == 'qty') {
            if (isset($condition['from'])) {
                $this->getSelect()->where($this->getCheckSqlForQty().' >= ?', $condition['from']);
            }

            if (isset($condition['to'])) {
                $this->getSelect()->where($this->getCheckSqlForQty().' <= ?', $condition['to']);
            }

            return $this;
        }

        return parent::addAttributeToFilter($attribute, $condition, $joinType);
    }

    //########################################

    public function addAttributeToSort($attribute, $dir = self::SORT_ORDER_ASC)
    {
        if ($attribute == 'qty') {
            return $this->getSelect()->order($this->getCheckSqlForQty(). ' ' . $dir);
        }

        return parent::addAttributeToSort($attribute, $dir);
    }

    //########################################
}

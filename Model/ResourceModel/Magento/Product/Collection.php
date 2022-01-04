<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Magento\Product;

use Magento\Catalog\Api\Data\ProductAttributeInterface;

/**
 * Class \Ess\M2ePro\Model\ResourceModel\Magento\Product\Collection
 */
class Collection extends \Magento\Catalog\Model\ResourceModel\Product\Collection
{
    /** @var bool  */
    protected $listingProductMode = false;

    /** @var \Ess\M2ePro\Model\Listing */
    protected $listing;

    /** @var string */
    protected $componentMode;

    /** @var bool  */
    protected $isNeedToInjectPrices = false;

    /** @var \Ess\M2ePro\Helper\Factory */
    protected $helperFactory;

    /** @var \Ess\M2ePro\Model\Factory */
    protected $modelFactory;

    /** @var \Ess\M2ePro\Model\ActiveRecord\Factory */
    protected $activeRecordFactory;

    /** @var \Magento\Framework\ObjectManagerInterface */
    protected $objectManager;

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
        $this->helperFactory = $helperFactory;
        $this->modelFactory = $modelFactory;
        $this->activeRecordFactory = $activeRecordFactory;
        $this->objectManager = $objectManager;

        parent::__construct(
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
            $connection
        );
    }

    //########################################

    public function setListingProductModeOn()
    {
        $this->listingProductMode = true;

        $this->_setIdFieldName('id');

        return $this;
    }

    public function setListing($value)
    {
        if (!($value instanceof \Ess\M2ePro\Model\Listing)) {
            $value = $this->activeRecordFactory->getCachedObjectLoaded('Listing', $value);
        }
        $this->listing = $value;
        return $this;
    }

    public function setComponentMode($componentMode)
    {
        $components = $this->helperFactory->getObject('Component')->getComponents();

        if (!in_array($componentMode, $components)) {
            throw new \Ess\M2ePro\Model\Exception\Logic(
                "Wrong component provided [$componentMode]"
            );
        }

        $this->componentMode = $componentMode;
        return $this;
    }

    public function setIsNeedToInjectPrices($value)
    {
        $this->isNeedToInjectPrices = $value;
        return $this;
    }

    //########################################

    public function getAllIds($limit = null, $offset = null)
    {
        $idsSelect = clone $this->getSelect();
        $idsSelect->reset(\Magento\Framework\DB\Select::ORDER);
        $idsSelect->reset(\Magento\Framework\DB\Select::LIMIT_COUNT);
        $idsSelect->reset(\Magento\Framework\DB\Select::LIMIT_OFFSET);

        if (!$this->listingProductMode) {
            // some of filtered field using HAVING (QTY for ex.) so we can't reset selecting columns
            $idsSelect->columns('e.' . $this->getEntity()->getIdFieldName());
            $idsSelect->limit($limit, $offset);
            $idsSelect->resetJoinLeft();

            return $this->getConnection()->fetchCol($idsSelect, $this->_bindParams);
        }

        // hack for selecting listing product ids instead entity ids

        $idsSelect->columns('lp.' . $this->getIdFieldName());
        $idsSelect->limit($limit, $offset);

        $data = $this->getConnection()->fetchAll($idsSelect, $this->_bindParams);

        $ids = [];
        foreach ($data as $row) {
            $ids[] = $row[$this->getIdFieldName()];
        }

        return $ids;
    }

    //########################################

    /**
     * @return int
     * @throws \Zend_Db_Select_Exception
     */
    public function getSize()
    {
        if ($this->_totalRecords === null) {
            $this->_renderFilters();
            $countSelect = $this->_getClearSelect();

            $tableAlias = 'lp';

            if (!$this->listingProductMode) {
                $tableAlias = self::MAIN_TABLE_ALIAS;
                $countSelect->reset(\Magento\Framework\DB\Select::GROUP);
            }

            $countSelect->columns("{$tableAlias}.{$this->getIdFieldName()}");

            $query = <<<SQL
SELECT COUNT(DISTINCT temp_table.{$this->getIdFieldName()}) FROM ({$countSelect->__toString()}) temp_table
SQL;

            $this->_totalRecords = $this->getConnection()->fetchOne($query, $this->_bindParams);
        }
        return intval($this->_totalRecords);
    }

    /**
     * @return \Magento\Framework\DB\Select
     * @throws \Zend_Db_Select_Exception
     */
    protected function _getClearSelect()
    {
        $havingColumns = $this->getHavingColumns();
        $parentSelect  = parent::_getClearSelect();

        if (empty($havingColumns)) {
            return $parentSelect;
        }

        foreach ($this->getSelect()->getPart('columns') as $columnData) {
            if (in_array($columnData[2], $havingColumns, true)) {
                $parentSelect->columns([$columnData[2] => $columnData[1]], $columnData[0]);
            }
        }

        return $parentSelect;
    }

    /**
     * @return array
     * @throws \Zend_Db_Select_Exception
     */
    protected function getHavingColumns()
    {
        $having = $this->getSelect()->getPart('having');

        if (empty($having)) {
            return [];
        }

        $columnsInHaving = [];

        foreach ($having as $havingPart) {
            preg_match_all(
                '/((`{0,1})\w+(`{0,1}))' .
                '( = | > | < | >= | <= | <> | <=> | != | LIKE | NOT | BETWEEN | IS NULL| IS NOT NULL| IN\(.*?\))/i',
                $havingPart,
                $matches
            );

            foreach ($matches[1] as $match) {
                $columnsInHaving[] = trim($match);
            }
        }

        return array_unique($columnsInHaving);
    }

    //########################################

    /**
     * Price Sorting Hack
     */
    public function addAttributeToSort($attribute, $dir = self::SORT_ORDER_ASC)
    {
        if ($attribute === 'min_online_price' || $attribute === 'max_online_price') {
            $this->getSelect()->order($attribute . ' ' . $dir);
            return $this;
        }

        return parent::addAttributeToSort($attribute, $dir);
    }

    //########################################

    public function joinIndexerParent()
    {
        if (!in_array($this->listing->getComponentMode(), [
            \Ess\M2ePro\Helper\Component\Ebay::NICK,
            \Ess\M2ePro\Helper\Component\Amazon::NICK,
            \Ess\M2ePro\Helper\Component\Walmart::NICK
        ])) {
            throw new \Ess\M2ePro\Model\Exception\Logic(
                "This component is not supported [{$this->listing->getComponentMode()}]"
            );
        }

        /** @var \Ess\M2ePro\Model\Listing\Product\Indexer\VariationParent\Manager $manager */
        $manager = $this->modelFactory->getObject('Listing_Product_Indexer_VariationParent_Manager', [
            'listing' => $this->listing
        ]);
        $manager->prepare();

        if ($this->listing->isComponentModeAmazon()) {
            $this->joinAmazonIndexerParent();
        } elseif ($this->listing->isComponentModeEbay()) {
            $this->joinEbayIndexerParent();
        } elseif ($this->listing->isComponentModeWalmart()) {
            $this->joinWalmartIndexerParent();
        }
    }

    private function joinAmazonIndexerParent()
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Amazon\Listing\Product\Indexer\VariationParent $resource */
        $resource = $this->activeRecordFactory->getObject(
            'Amazon_Listing_Product_Indexer_VariationParent'
        )->getResource();

        $this->getSelect()->joinLeft(
            ['indexer' => $resource->getMainTable()],
            '(`alp`.`listing_product_id` = `indexer`.`listing_product_id`)',
            [
                'min_online_regular_price' => new \Zend_Db_Expr('IF(
                    (`indexer`.`min_regular_price` IS NULL),
                    IF(
                      `alp`.`online_regular_sale_price_start_date` IS NOT NULL AND
                      `alp`.`online_regular_sale_price_end_date` IS NOT NULL AND
                      `alp`.`online_regular_sale_price_start_date` <= CURRENT_DATE() AND
                      `alp`.`online_regular_sale_price_end_date` >= CURRENT_DATE(),
                      `alp`.`online_regular_sale_price`,
                      `alp`.`online_regular_price`
                    ),
                    `indexer`.`min_regular_price`
                )'),
                'max_online_regular_price' => new \Zend_Db_Expr('IF(
                    (`indexer`.`max_regular_price` IS NULL),
                    IF(
                      `alp`.`online_regular_sale_price_start_date` IS NOT NULL AND
                      `alp`.`online_regular_sale_price_end_date` IS NOT NULL AND
                      `alp`.`online_regular_sale_price_start_date` <= CURRENT_DATE() AND
                      `alp`.`online_regular_sale_price_end_date` >= CURRENT_DATE(),
                      `alp`.`online_regular_sale_price`,
                      `alp`.`online_regular_price`
                    ),
                    `indexer`.`max_regular_price`
                )'),
                'min_online_business_price' => new \Zend_Db_Expr('IF(
                    (`indexer`.`min_business_price` IS NULL),
                    `alp`.`online_business_price`,
                    `indexer`.`min_business_price`
                )'),
                'max_online_business_price' => new \Zend_Db_Expr('IF(
                    (`indexer`.`max_business_price` IS NULL),
                    `alp`.`online_business_price`,
                    `indexer`.`max_business_price`
                )'),
                'min_online_price' => new \Zend_Db_Expr('IF(
                    (`indexer`.`min_regular_price` IS NULL AND `indexer`.`min_business_price` IS NULL),
                    IF(
                       `alp`.`online_regular_price` IS NULL,
                       `alp`.`online_business_price`,
                       IF(
                          `alp`.`online_regular_sale_price_start_date` IS NOT NULL AND
                          `alp`.`online_regular_sale_price_end_date` IS NOT NULL AND
                          `alp`.`online_regular_sale_price_start_date` <= CURRENT_DATE() AND
                          `alp`.`online_regular_sale_price_end_date` >= CURRENT_DATE(),
                          `alp`.`online_regular_sale_price`,
                          `alp`.`online_regular_price`
                       )
                    ),
                    IF(
                        `indexer`.`min_regular_price` IS NULL,
                        `indexer`.`min_business_price`,
                        `indexer`.`min_regular_price`
                    )
                )'),
                'max_online_price' => new \Zend_Db_Expr('IF(
                    `indexer`.`max_regular_price` IS NULL AND `indexer`.`max_business_price` IS NULL,
                    IF(
                      `alp`.`online_regular_price` IS NULL,
                       `alp`.`online_business_price`,
                       IF(
                          `alp`.`online_regular_sale_price_start_date` IS NOT NULL AND
                          `alp`.`online_regular_sale_price_end_date` IS NOT NULL AND
                          `alp`.`online_regular_sale_price_start_date` <= CURRENT_DATE() AND
                          `alp`.`online_regular_sale_price_end_date` >= CURRENT_DATE(),
                          `alp`.`online_regular_sale_price`,
                          `alp`.`online_regular_price`
                       )
                    ),
                    IF(
                        `indexer`.`max_regular_price` IS NULL,
                        `indexer`.`max_business_price`,
                        `indexer`.`max_regular_price`
                    )
                )'),
            ]
        );
    }

    private function joinEbayIndexerParent()
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Product\Indexer\VariationParent $resource */
        $resource = $this->activeRecordFactory->getObject(
            'Ebay_Listing_Product_Indexer_VariationParent'
        )->getResource();

        $this->getSelect()->joinLeft(
            ['indexer' => $resource->getMainTable()],
            '(`elp`.`listing_product_id` = `indexer`.`listing_product_id`)',
            [
                'min_online_price' => new \Zend_Db_Expr('IF(
                    `indexer`.`min_price` IS NULL,
                    `elp`.`online_current_price`,
                    `indexer`.`min_price`
                )'),
                'max_online_price' => new \Zend_Db_Expr('IF(
                    `indexer`.`max_price` IS NULL,
                    `elp`.`online_current_price`,
                    `indexer`.`max_price`
                )'),
            ]
        );
    }

    private function joinWalmartIndexerParent()
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Walmart\Listing\Product\Indexer\VariationParent $resource */
        $resource = $this->activeRecordFactory->getObject('Walmart_Listing_Product_Indexer_VariationParent')
                                              ->getResource();

        $this->getSelect()->joinLeft(
            ['indexer' => $resource->getMainTable()],
            '(`wlp`.`listing_product_id` = `indexer`.`listing_product_id`)',
            [
                'min_online_price' => new \Zend_Db_Expr('IF(
                `indexer`.`min_price` IS NULL,
                `wlp`.`online_price`,
                `indexer`.`min_price`
            )'),

                'max_online_price' => new \Zend_Db_Expr('IF(
                `indexer`.`max_price` IS NULL,
                `wlp`.`online_price`,
                `indexer`.`max_price`
            )'),
            ]
        );
    }

    //----------------------------------------

    protected function _afterLoad()
    {
        $result = parent::_afterLoad();

        if ($this->isNeedToInjectPrices) {
            $this->injectParentPrices();
        }

        return $result;
    }

    private function injectParentPrices()
    {
        if (!in_array($this->listing->getComponentMode(), [
            \Ess\M2ePro\Helper\Component\Ebay::NICK,
            \Ess\M2ePro\Helper\Component\Amazon::NICK,
            \Ess\M2ePro\Helper\Component\Walmart::NICK
        ])) {
            throw new \Ess\M2ePro\Model\Exception\Logic(
                "This component is not supported [{$this->listing->getComponentMode()}]"
            );
        }

        if ($this->listing->isComponentModeAmazon()) {
            $this->injectAmazonParentPrices();
        } elseif ($this->listing->isComponentModeEbay()) {
            $this->injectEbayParentPrices();
        } elseif ($this->listing->isComponentModeWalmart()) {
            $this->injectWalmartParentPrices();
        }
    }

    private function injectAmazonParentPrices()
    {
        $listingProductsData = [];
        foreach ($this as $product) {
            $listingProductsData[(int)$product->getData('id')] = [
                'min_online_regular_price'  => $product->getData('online_regular_price'),
                'max_online_regular_price'  => $product->getData('online_regular_price'),
                'min_online_business_price' => $product->getData('online_business_price'),
                'max_online_business_price' => $product->getData('online_business_price'),
            ];
        }

        if (empty($listingProductsData)) {
            return;
        }

        /** @var \Ess\M2ePro\Model\ResourceModel\Amazon\Listing\Product\Indexer\VariationParent $resource */
        $resource = $this->activeRecordFactory->getObject(
            'Amazon_Listing_Product_Indexer_VariationParent'
        )->getResource();

        $selectStmt = $resource->getBuildIndexSelect($this->listing);
        $selectStmt->where('malp.variation_parent_id IN (?)', array_keys($listingProductsData));

        $data = $this->getConnection()->fetchAll($selectStmt);
        foreach ($data as $row) {
            $listingProductsData[(int)$row['variation_parent_id']] = [
                'min_online_regular_price'  => $row['variation_min_regular_price'],
                'max_online_regular_price'  => $row['variation_max_regular_price'],
                'min_online_business_price' => $row['variation_min_business_price'],
                'max_online_business_price' => $row['variation_max_business_price'],
            ];
        }

        foreach ($this as $product) {
            if (isset($listingProductsData[(int)$product->getData('id')])) {
                $dataPart = $listingProductsData[(int)$product->getData('id')];
                $product->setData('min_online_regular_price', $dataPart['min_online_regular_price']);
                $product->setData('max_online_regular_price', $dataPart['max_online_regular_price']);
                $product->setData('min_online_business_price', $dataPart['min_online_business_price']);
                $product->setData('max_online_business_price', $dataPart['max_online_business_price']);
            }
        }
    }

    private function injectEbayParentPrices()
    {
        $listingProductsData = [];
        foreach ($this as $product) {
            $listingProductsData[(int)$product->getData('id')] = [
                'min_online_price' => $product->getData('online_current_price'),
                'max_online_price' => $product->getData('online_current_price'),
            ];
        }

        if (empty($listingProductsData)) {
            return;
        }

        /** @var \Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Product\Indexer\VariationParent $resource */
        $resource = $this->activeRecordFactory->getObject(
            'Ebay_Listing_Product_Indexer_VariationParent'
        )->getResource();

        $selectStmt = $resource->getBuildIndexSelect($this->listing);
        $selectStmt->where('mlpv.listing_product_id IN (?)', array_keys($listingProductsData));

        $data = $this->getConnection()->fetchAll($selectStmt);
        foreach ($data as $row) {
            $listingProductsData[(int)$row['listing_product_id']] = [
                'min_online_price' => $row['variation_min_price'],
                'max_online_price' => $row['variation_max_price'],
            ];
        }

        foreach ($this as $product) {
            if (isset($listingProductsData[(int)$product->getData('id')])) {
                $dataPart = $listingProductsData[(int)$product->getData('id')];
                $product->setData('min_online_price', $dataPart['min_online_price']);
                $product->setData('max_online_price', $dataPart['max_online_price']);
            }
        }
    }

    private function injectWalmartParentPrices()
    {
        $listingProductsData = [];
        foreach ($this as $product) {
            $listingProductsData[(int)$product->getData('id')] = [
                'min_online_price' => $product->getData('online_price'),
                'max_online_price' => $product->getData('online_price'),
            ];
        }

        if (empty($listingProductsData)) {
            return;
        }

        /** @var \Ess\M2ePro\Model\ResourceModel\Walmart\Listing\Product\Indexer\VariationParent $resource */
        $resource = $this->activeRecordFactory
            ->getObject('Walmart_Listing_Product_Indexer_VariationParent')->getResource();

        $selectStmt = $resource->getBuildIndexSelect($this->listing);
        $selectStmt->where('mwlp.variation_parent_id IN (?)', array_keys($listingProductsData));

        $data = $this->getConnection()->fetchAll($selectStmt);
        foreach ($data as $row) {
            $listingProductsData[(int)$row['variation_parent_id']] = [
                'min_online_price' => $row['variation_min_price'],
                'max_online_price' => $row['variation_max_price'],
            ];
        }

        foreach ($this as $product) {
            if (isset($listingProductsData[(int)$product->getData('id')])) {
                $dataPart = $listingProductsData[(int)$product->getData('id')];
                $product->setData('min_online_price', $dataPart['min_online_price']);
                $product->setData('max_online_price', $dataPart['max_online_price']);
            }
        }
    }

    //########################################

    public function joinStockItem()
    {
        if ($this->_storeId === null) {
            throw new \Ess\M2ePro\Model\Exception('Store view was not set.');
        }

        $this->joinTable(
            [
                'cisi' => $this->helperFactory->getObject('Module_Database_Structure')
                                    ->getTableNameWithPrefix('cataloginventory_stock_item')
            ],
            'product_id=entity_id',
            [
                'qty'         => 'qty',
                'is_in_stock' => 'is_in_stock'
            ],
            [
                'stock_id'   => $this->helperFactory->getObject('Magento\Stock')->getStockId($this->getStoreId()),
                'website_id' => $this->helperFactory->getObject('Magento\Stock')->getWebsiteId($this->getStoreId())
            ],
            'left'
        );

        return $this;
    }

    //########################################

    /**
     * Compatibility with Magento Enterprise (Staging modules) - entity_id column issue
     */
    public function joinTable($table, $bind, $fields = null, $cond = null, $joinType = 'inner')
    {
        /** @var \Ess\M2ePro\Helper\Magento\Staging $helper */
        $helper = $this->helperFactory->getObject('Magento\Staging');

        if ($helper->isInstalled() &&
            $helper->isStagedTable($table, ProductAttributeInterface::ENTITY_TYPE_CODE) &&
            strpos($bind, 'entity_id') !== false) {
            $bind = str_replace(
                'entity_id',
                $helper->getTableLinkField(ProductAttributeInterface::ENTITY_TYPE_CODE),
                $bind
            );
        }

        return parent::joinTable($table, $bind, $fields, $cond, $joinType);
    }

    //########################################
}

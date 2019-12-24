<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Ebay;

use Magento\Framework\DB\Select;

/**
 * Class \Ess\M2ePro\Model\ResourceModel\Ebay\Listing
 */
class Listing extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Component\Child\AbstractModel
{
    protected $catalogProductAction;
    protected $productFactory;

    protected $_isPkAutoIncrement = false;

    //########################################

    public function _construct()
    {
        $this->_init('m2epro_ebay_listing', 'listing_id');
        $this->_isPkAutoIncrement = false;
    }

    //########################################

    public function __construct(
        \Magento\Catalog\Model\Product\Action $catalogProductAction,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory,
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        $connectionName = null
    ) {
        $this->catalogProductAction = $catalogProductAction;
        $this->productFactory = $productFactory;
        parent::__construct($helperFactory, $activeRecordFactory, $parentFactory, $context, $connectionName);
    }

    //########################################

    public function updateStatisticColumns()
    {
        $this->updateProductsSoldCount();
        $this->updateItemsActiveCount();
        $this->updateItemsSoldCount();
    }

    // ---------------------------------------

    private function updateProductsSoldCount()
    {
        $lpTable = $this->activeRecordFactory->getObject('Listing\Product')->getResource()->getMainTable();

        $select = $this->getConnection()
                       ->select()
                       ->from($lpTable, new \Zend_Db_Expr('COUNT(*)'))
                       ->where("`listing_id` = `{$this->getMainTable()}`.`listing_id`")
                       ->where("`status` = ?", (int)\Ess\M2ePro\Model\Listing\Product::STATUS_SOLD);

        $query = "UPDATE `{$this->getMainTable()}`
                  SET `products_sold_count` =  (".$select->__toString().")";

        $this->getConnection()->query($query);
    }

    private function updateItemsActiveCount()
    {
        $lTable = $this->activeRecordFactory->getObject('Listing')->getResource()->getMainTable();
        $lpTable = $this->activeRecordFactory->getObject('Listing\Product')->getResource()->getMainTable();
        $elpTable = $this->activeRecordFactory->getObject('Ebay_Listing_Product')->getResource()->getMainTable();

        $select = $this->getConnection()
                       ->select()
                       ->from(
                           ['lp' => $lpTable],
                           new \Zend_Db_Expr('SUM(`online_qty` - `online_qty_sold`)')
                       )
                       ->join(
                           ['elp' => $elpTable],
                           'lp.id = elp.listing_product_id',
                           []
                       )
                       ->where("`listing_id` = `{$lTable}`.`id`")
                       ->where("`status` = ?", (int)\Ess\M2ePro\Model\Listing\Product::STATUS_LISTED);

        $query = "UPDATE `{$lTable}`
                  SET `items_active_count` =  IFNULL((".$select->__toString()."),0)
                  WHERE `component_mode` = '".\Ess\M2ePro\Helper\Component\Ebay::NICK."'";

        $this->getConnection()->query($query);
    }

    private function updateItemsSoldCount()
    {
        $lpTable = $this->activeRecordFactory->getObject('Listing\Product')->getResource()->getMainTable();
        $elpTable = $this->activeRecordFactory->getObject('Ebay_Listing_Product')->getResource()->getMainTable();

        $select = $this->getConnection()
                       ->select()
                       ->from(
                           ['lp' => $lpTable],
                           new \Zend_Db_Expr('SUM(`online_qty_sold`)')
                       )
                       ->join(
                           ['elp' => $elpTable],
                           'lp.id = elp.listing_product_id',
                           []
                       )
                       ->where("`listing_id` = `{$this->getMainTable()}`.`listing_id`");

        $query = "UPDATE `{$this->getMainTable()}`
                  SET `items_sold_count` =  (".$select->__toString().")";

        $this->getConnection()->query($query);
    }

    //########################################

    public function getProductCollection($listingId)
    {
        $collection = $this->productFactory->create()->getCollection();

        $lpTable = $this->activeRecordFactory->getObject('Listing\Product')->getResource()->getMainTable();
        $elpTable = $this->activeRecordFactory->getObject('Ebay_Listing_Product')->getResource()->getMainTable();

        $collection->joinTable(
            ['lp' => $lpTable],
            'product_id=entity_id',
            ['id' => 'id'],
            '{{table}}.listing_id='.(int)$listingId
        );

        $collection->joinTable(
            ['elp' => $elpTable],
            'listing_product_id=id',
            ['listing_product_id' => 'listing_product_id']
        );

        return $collection;
    }

    //########################################

    public function updateMotorsAttributesData(
        $listingId,
        array $listingProductIds,
        $attribute,
        $data,
        $overwrite = false
    ) {
        if (count($listingProductIds) == 0) {
            return;
        }

        $listing = $this->parentFactory->getCachedObjectLoaded(
            \Ess\M2ePro\Helper\Component\Ebay::NICK,
            'Listing',
            $listingId
        );
        $storeId = (int)$listing->getStoreId();

        $listingProductsCollection = $this->activeRecordFactory->getObject('Listing\Product')->getCollection();
        $listingProductsCollection->addFieldToFilter('id', ['in' => $listingProductIds]);
        $listingProductsCollection->getSelect()->reset(\Zend_Db_Select::COLUMNS);
        $listingProductsCollection->getSelect()->columns(['product_id']);

        $productIds = $listingProductsCollection->getColumnValues('product_id');

        if ($overwrite) {
            $this->catalogProductAction->updateAttributes(
                $productIds,
                [$attribute => $data],
                $storeId
            );
            return;
        }

        $productCollection = $this->productFactory->create()->getCollection();
        $productCollection->setStoreId($storeId);
        $productCollection->addFieldToFilter('entity_id', ['in' => $productIds]);
        $productCollection->addAttributeToSelect($attribute);

        foreach ($productCollection->getItems() as $itemId => $item) {
            $currentAttributeValue = $item->getData($attribute);
            $newAttributeValue = $data;

            if (!empty($currentAttributeValue)) {
                $newAttributeValue = $currentAttributeValue . ',' . $data;
            }

            $this->catalogProductAction->updateAttributes(
                [$itemId],
                [$attribute => $newAttributeValue],
                $storeId
            );
        }
    }

    //########################################

    public function getTemplateCategoryIds($listingId)
    {
        $lpTable  = $this->activeRecordFactory->getObject('Listing\Product')->getResource()->getMainTable();
        $elpTable = $this->activeRecordFactory->getObject('Ebay_Listing_Product')->getResource()->getMainTable();

        $select = $this->getConnection()
            ->select()
            ->from(['elp' => $elpTable])
            ->joinLeft(['lp' => $lpTable], 'lp.id = elp.listing_product_id')
            ->reset(\Zend_Db_Select::COLUMNS)
            ->columns(['template_category_id'])
            ->where('lp.listing_id = ?', $listingId)
            ->where('template_category_id IS NOT NULL');

        $ids = $select->query()->fetchAll(\PDO::FETCH_COLUMN);

        return array_unique($ids);
    }

    //########################################

    public function getUsedProductsIds($listingId)
    {
        $collection = $this->activeRecordFactory->getObject('Listing\Product')->getCollection();
        $collection->addFieldToFilter('listing_id', $listingId);

        $collection->getSelect()->reset(Select::COLUMNS);
        $collection->getSelect()->columns(['product_id']);

        $collection->getSelect()->joinLeft(
            [
                'lpv' => $this->activeRecordFactory->getObject('Listing\Product\Variation')
                                                    ->getResource()->getMainTable()
            ],
            'lpv.listing_product_id = main_table.id',
            []
        );
        $collection->getSelect()->joinLeft(
            [
                'lpvo' => $this->activeRecordFactory->getObject('Listing\Product\Variation\Option')
                                                    ->getResource()->getMainTable()
            ],
            'lpvo.listing_product_variation_id = lpv.id',
            ['variation_product_id' => 'product_id']
        );

        $products = [];

        foreach ($collection->getItems() as $item) {
            $productId = $item->getData('product_id');
            $variationProductId = $item->getData('variation_product_id');

            $products[$productId] = $productId;
            !empty($variationProductId) && $products[$variationProductId] = $variationProductId;
        }

        return array_values($products);
    }

    //########################################
}

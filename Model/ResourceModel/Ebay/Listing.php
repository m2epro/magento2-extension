<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Ebay;

class Listing extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Component\Child\AbstractDb
{
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
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory,
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        $connectionName = null
    )
    {
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
                       ->from($lpTable,new \Zend_Db_Expr('COUNT(*)'))
                       ->where("`listing_id` = `{$this->getMainTable()}`.`listing_id`")
                       ->where("`status` = ?",(int)\Ess\M2ePro\Model\Listing\Product::STATUS_SOLD);

        $query = "UPDATE `{$this->getMainTable()}`
                  SET `products_sold_count` =  (".$select->__toString().")";

        $this->getConnection()->query($query);
    }

    private function updateItemsActiveCount()
    {
        $lTable = $this->activeRecordFactory->getObject('Listing')->getResource()->getMainTable();
        $lpTable = $this->activeRecordFactory->getObject('Listing\Product')->getResource()->getMainTable();
        $elpTable = $this->activeRecordFactory->getObject('Ebay\Listing\Product')->getResource()->getMainTable();

        $select = $this->getConnection()
                       ->select()
                       ->from(
                          array('lp' => $lpTable),
                          new \Zend_Db_Expr('SUM(`online_qty` - `online_qty_sold`)')
                       )
                       ->join(
                          array('elp' => $elpTable),
                          'lp.id = elp.listing_product_id',
                          array()
                       )
                       ->where("`listing_id` = `{$lTable}`.`id`")
                       ->where("`status` = ?",(int)\Ess\M2ePro\Model\Listing\Product::STATUS_LISTED);

        $query = "UPDATE `{$lTable}`
                  SET `items_active_count` =  IFNULL((".$select->__toString()."),0)
                  WHERE `component_mode` = '".\Ess\M2ePro\Helper\Component\Ebay::NICK."'";

        $this->getConnection()->query($query);
    }

    private function updateItemsSoldCount()
    {
        $lpTable = $this->activeRecordFactory->getObject('Listing\Product')->getResource()->getMainTable();
        $elpTable = $this->activeRecordFactory->getObject('Ebay\Listing\Product')->getResource()->getMainTable();

        $select = $this->getConnection()
                       ->select()
                       ->from(
                            array('lp' => $lpTable),
                            new \Zend_Db_Expr('SUM(`online_qty_sold`)')
                       )
                       ->join(
                            array('elp' => $elpTable),
                            'lp.id = elp.listing_product_id',
                            array()
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
        $elpTable = $this->activeRecordFactory->getObject('Ebay\Listing\Product')->getResource()->getMainTable();

        $collection->joinTable(
            array('lp' => $lpTable),
            'product_id=entity_id',
            array('id' => 'id'),
            '{{table}}.listing_id='.(int)$listingId
        );

        $collection->joinTable(
            array('elp' => $elpTable),
            'listing_product_id=id',
            array('listing_product_id' => 'listing_product_id')
        );

        return $collection;
    }

    // TODO NOT SUPPORTED FEATURES "ebay parts compatibility"
//    public function updateMotorsAttributesData($listingId,
//                                               array $listingProductIds,
//                                               $attribute,
//                                               $data,
//                                               $overwrite = false) {
//        if (count($listingProductIds) == 0) {
//            return;
//        }
//
//        $listing = $this->getHelper('Component\Ebay')->getCachedObject('Listing', $listingId);
//        $storeId = (int)$listing->getStoreId();
//
//        $listingProductsCollection = $this->modelFactory->getObject('Listing\Product')->getCollection();
//        $listingProductsCollection->addFieldToFilter('id', array('in' => $listingProductIds));
//        $listingProductsCollection->getSelect()->reset(\Zend_Db_Select::COLUMNS);
//        $listingProductsCollection->getSelect()->columns(array('product_id'));
//
//        $productIds = $listingProductsCollection->getColumnValues('product_id');
//
//        if ($overwrite) {
//            Mage::getSingleton('catalog/product_action')->updateAttributes(
//                $productIds,
//                array($attribute => $data),
//                $storeId
//            );
//            return;
//        }
//
//        $productCollection = Mage::getModel('catalog/product')->getCollection();
//        $productCollection->setStoreId($storeId);
//        $productCollection->addFieldToFilter('entity_id', array('in' => $productIds));
//        $productCollection->addAttributeToSelect($attribute);
//
//        foreach ($productCollection->getItems() as $itemId => $item) {
//
//            $currentAttributeValue = $item->getData($attribute);
//            $newAttributeValue = $data;
//
//            if (!empty($currentAttributeValue)) {
//                $newAttributeValue = $currentAttributeValue . ',' . $data;
//            }
//
//            Mage::getSingleton('catalog/product_action')->updateAttributes(
//                array($itemId),
//                array($attribute => $newAttributeValue),
//                $storeId
//            );
//        }
//    }

    //########################################
}
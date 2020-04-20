<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Ebay\Listing;

/**
 * Class \Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Product
 */
class Product extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Component\Child\AbstractModel
{
    protected $_isPkAutoIncrement = false;

    //########################################

    public function _construct()
    {
        $this->_init('m2epro_ebay_listing_product', 'listing_product_id');
        $this->_isPkAutoIncrement = false;
    }

    //########################################

    public function getTemplateCategoryIds(array $listingProductIds)
    {
        $select = $this->getConnection()
                       ->select()
                       ->from(['elp' => $this->getMainTable()])
                       ->reset(\Zend_Db_Select::COLUMNS)
                       ->columns(['template_category_id'])
                       ->where('listing_product_id IN (?)', $listingProductIds)
                       ->where('template_category_id IS NOT NULL');

        $ids = $select->query()->fetchAll(\PDO::FETCH_COLUMN);

        return array_unique($ids);
    }

    public function getTemplateOtherCategoryIds(array $listingProductIds)
    {
        $select = $this->getConnection()
                       ->select()
                       ->from(['elp' => $this->getMainTable()])
                       ->reset(\Zend_Db_Select::COLUMNS)
                       ->columns(['template_other_category_id'])
                       ->where('listing_product_id IN (?)', $listingProductIds)
                       ->where('template_other_category_id IS NOT NULL');

        $ids = $select->query()->fetchAll(\PDO::FETCH_COLUMN);

        return array_unique($ids);
    }

    //########################################
}

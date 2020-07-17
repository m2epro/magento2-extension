<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Category\View\Tabs\ProductsSecondary;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Ebay\Category\View\Tabs\ProductsSecondary\Grid
 */
class Grid extends \Ess\M2ePro\Block\Adminhtml\Ebay\Category\View\Tabs\AbstractGrid
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        $this->showAdvancedFilterProductsOption = false;
        $this->setId('ebayCategoryViewProductsSecondaryGrid');
    }

    //########################################

    protected function _prepareCollection()
    {
        /** @var $collection \Ess\M2ePro\Model\ResourceModel\Magento\Product\Collection */
        $collection = $this->magentoProductCollectionFactory->create();
        $collection->setListingProductModeOn();
        $collection->addAttributeToSelect('sku');
        $collection->addAttributeToSelect('name');

        $collection->joinTable(
            ['lp' => $this->activeRecordFactory->getObject('Listing\Product')->getResource()->getMainTable()],
            'product_id=entity_id',
            [
                'id'              => 'id',
                'status'          => 'status',
                'component_mode'  => 'component_mode',
                'listing_id'      => 'listing_id',
                'additional_data' => 'additional_data'
            ]
        );
        $collection->joinTable(
            ['elp' => $this->activeRecordFactory->getObject('Ebay_Listing_Product')->getResource()->getMainTable()],
            'listing_product_id=id',
            [
                'listing_product_id'    => 'listing_product_id',
                'end_date'              => 'end_date',
                'start_date'            => 'start_date',
                'online_title'          => 'online_title',
                'online_sku'            => 'online_sku',
                'available_qty'         => new \Zend_Db_Expr('(elp.online_qty - elp.online_qty_sold)'),
                'ebay_item_id'          => 'ebay_item_id',
                'online_main_category'  => 'online_main_category',
                'online_qty_sold'       => 'online_qty_sold',
                'online_bids'           => 'online_bids',
                'online_start_price'    => 'online_start_price',
                'online_current_price'  => 'online_current_price',
                'online_reserve_price'  => 'online_reserve_price',
                'online_buyitnow_price' => 'online_buyitnow_price',
                'template_category_secondary_id'  => 'template_category_secondary_id'
            ]
        );
        $collection->joinTable(
            ['l' => $this->activeRecordFactory->getObject('Listing')->getResource()->getMainTable()],
            'id=listing_id',
            [
                'store_id'       => 'store_id',
                'account_id'     => 'account_id',
                'marketplace_id' => 'marketplace_id'
            ]
        );
        $collection->joinTable(
            ['em' => $this->activeRecordFactory->getObject('Ebay\Marketplace')->getResource()->getMainTable()],
            'marketplace_id=marketplace_id',
            ['currency' => 'currency']
        );
        $collection->joinTable(
            ['ei' => $this->activeRecordFactory->getObject('Ebay\Item')->getResource()->getMainTable()],
            'id=ebay_item_id',
            ['item_id' => 'item_id'],
            null,
            'left'
        );
        /** @var \Ess\M2ePro\Model\Ebay\Template\Category $templateCategory */
        $templateCategory = $this->activeRecordFactory->getObjectLoaded(
            'Ebay_Template_Category',
            $this->getRequest()->getParam('template_id')
        );
        $collection->joinTable(
            ['etc' => $this->activeRecordFactory->getObject('Ebay_Template_Category')->getResource()->getMainTable()],
            'id=template_category_secondary_id',
            [
                'category_id'        => 'category_id',
                'category_attribute' => 'category_attribute',
                'is_custom_template' => 'is_custom_template'
            ]
        );

        if ($templateCategory->isCategoryModeEbay()) {
            $collection->addFieldToFilter('category_id', $templateCategory->getCategoryId());
        }

        if ($templateCategory->isCategoryModeAttribute()) {
            $collection->addFieldToFilter('category_attribute', $templateCategory->getCategoryAttribute());
        }

        $collection->addFieldToFilter('marketplace_id', $templateCategory->getMarketplaceId());

        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        parent::_prepareColumns();
        $this->removeColumn('is_custom_template');
    }

    //########################################

    public function getGridUrl()
    {
        return $this->getUrl('*/*/viewSecondaryGrid', ['_current'=>true]);
    }

    //########################################
}

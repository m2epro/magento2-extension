<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Product\Add\SourceMode\Category\Summary;

class Grid extends \Ess\M2ePro\Block\Adminhtml\Category\Grid
{
    //########################################

    public function setProductsForEachCategory($productsForEachCategory)
    {
        $this->setData('products_for_each_category',$productsForEachCategory);
        return $this;
    }

    public function getProductsForEachCategory()
    {
        return $this->getData('products_for_each_category');
    }

    public function setProductsIds($productsIds)
    {
        $this->setData('products_ids',$productsIds);
        return $this;
    }

    public function getProductsIds()
    {
        return $this->getData('products_ids');
    }

    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('ListingProductSourceCategoriesSummaryGrid');
        // ---------------------------------------

        // Set default values
        // ---------------------------------------
        $this->setFilterVisibility(false);
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
        // ---------------------------------------
    }

    //########################################

    protected function _prepareCollection()
    {
        $collection = $this->categoryCollectionFactory->create();
        $collection->addAttributeToSelect('name');

        $dbSelect = $collection->getConnection()
             ->select()
             ->from($collection->getConnection()->getTableName('catalog_category_product'), 'category_id')
             ->where('`product_id` IN(?)',$this->getProductsIds());

        $collection->getSelect()->where('entity_id IN ('.$dbSelect->__toString().')');

        $this->setCollection($collection);

        parent::_prepareCollection();

        return $this;
    }

    //########################################

    protected function _prepareMassaction()
    {
        // Set massaction identifiers
        // ---------------------------------------
        $this->setMassactionIdField('entity_id');
        $this->getMassactionBlock()->setFormFieldName('ids');
        // ---------------------------------------

        $this->getMassactionBlock()->addItem('remove', array(
             'label'    => $this->__('Remove'),
        ));

        // ---------------------------------------

        return parent::_prepareMassaction();
    }

    //########################################

    protected function _prepareColumns()
    {
        $this->addColumn('magento_category', array(
            'header'    => $this->__('Magento Category'),
            'align'     => 'left',
            'type'      => 'text',
            'index'     => 'name',
            'filter'    => false,
            'sortable'  => false,
            'frame_callback' => array($this, 'callbackColumnMagentoCategory')
        ));

        $this->addColumn('action', array(
            'header'    => $this->__('Action'),
            'align'     => 'center',
            'width'     => '75px',
            'type'      => 'text',
            'filter'    => false,
            'sortable'  => false,
            'frame_callback' => array($this, 'callbackColumnActions')
        ));

        return parent::_prepareColumns();
    }

    //########################################

    public function callbackColumnMagentoCategory($value, $row, $column, $isExport)
    {
        $productsForEachCategory = $this->getProductsForEachCategory();

        return parent::callbackColumnMagentoCategory($value, $row, $column, $isExport) .
               ' ('.$productsForEachCategory[$row->getId()].')';
    }

    //########################################

    public function callbackColumnActions($value, $row, $column, $isExport)
    {
        return <<<HTML
<a  href="javascript:"
    onclick="AmazonListingProductAddSourceModeCategorySummaryGridObj.selectByRowId('{$row->getId()}');
             AmazonListingProductAddSourceModeCategorySummaryGridObj.remove()"
   >{$this->__('Remove')}</a>
HTML;
    }

    //########################################

    protected function _toHtml()
    {
        $beforeHtml = <<<HTML
<style>

    div#{$this->getId()} div.grid {
        overflow-y: auto !important;
        height: 263px !important;
    }

    div#{$this->getId()} div.grid th {
        padding: 2px 4px !important;
    }

    div#{$this->getId()} div.grid td {
        padding: 2px 4px !important;
    }

    div#{$this->getId()} table.massaction div.right {
        display: block;
    }

    div#{$this->getId()} table.massaction td {
        padding: 1px 8px;
    }

</style>
HTML;

        $help = $this->createBlock('HelpBlock')->setData([
            'content' => $this->__(
                'The Quantity of chosen Products in each Category is shown in brackets.  <br/>
                 If the Product belongs to several Categories,
                 it is shown in each Category.
                 And if you remove the Category with such Product it will be subtracted from each Category.'
            )
        ]);

        $beforeHtml .= <<<HTML
<div style="margin: 15px 0 10px 0">{$help->toHtml()}</div>
HTML;

        $path = 'amazon_listing_product_add/removeSessionProductsByCategory';
        $this->jsUrl->add($this->getUrl('*/' . $path), $path);

        if (!$this->getRequest()->getParam('grid')) {
            $this->js->add(<<<JS
    require([
        'M2ePro/Amazon/Listing/Product/Add/SourceMode/Category/Summary/Grid'
    ],function() {
        AmazonListingProductAddSourceModeCategorySummaryGridObj
                = new AmazonListingProductAddSourceModeCategorySummaryGrid(
            '{$this->getId()}'
        );
    });
JS
            );
        }

        $this->js->add(<<<JS
    require([
        'M2ePro/Amazon/Listing/Product/Add/SourceMode/Category/Summary/Grid'
    ],function() {
        {$this->getCollection()->getSize()} || closeCategoriesPopup();
        AmazonListingProductAddSourceModeCategorySummaryGridObj.afterInitPage();
    });
JS
        );

        if ($this->getRequest()->getParam('grid')) {
            $beforeHtml = NULL;
        }

        return $beforeHtml . parent::_toHtml();
    }

    //########################################

    public function getGridUrl()
    {
        return $this->getCurrentUrl(array('grid' => true));
    }

    //########################################

    public function getRowUrl($row)
    {
        return false;
    }

    //########################################
}

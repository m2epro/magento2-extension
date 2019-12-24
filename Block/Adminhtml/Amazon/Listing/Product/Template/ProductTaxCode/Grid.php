<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Product\Template\ProductTaxCode;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Product\Template\ProductTaxCode\Grid
 */
class Grid extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractGrid
{
    protected $productsIds;

    //########################################

    public function _construct()
    {
        parent::_construct();

        $this->setId('amazonTemplateProductTaxCodeGrid');

        // Set default values
        // ---------------------------------------
        $this->setFilterVisibility(true);
        $this->setDefaultSort('title');
        $this->setDefaultDir('ASC');
        $this->setSaveParametersInSession(false);
        $this->setUseAjax(true);

        // ---------------------------------------
    }

    // ---------------------------------------

    /**
     * @param mixed $productsIds
     */
    public function setProductsIds($productsIds)
    {
        $this->productsIds = $productsIds;
    }

    /**
     * @return mixed
     */
    public function getProductsIds()
    {
        return $this->productsIds;
    }

    // ---------------------------------------

    protected function _prepareCollection()
    {
        $this->setNoTemplatesText();

        $collection = $this->activeRecordFactory->getObject('Amazon_Template_ProductTaxCode')->getCollection();
        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('title', [
            'header'       => $this->__('Title'),
            'align'        => 'left',
            'type'         => 'text',
            'index'        => 'title',
            'filter_index' => 'title',
            'escape'       => false,
            'sortable'     => true,
            'frame_callback' => [$this, 'callbackColumnTitle']
        ]);

        $this->addColumn('action', [
            'header'       => $this->__('Action'),
            'align'        => 'left',
            'type'         => 'number',
            'width'        => '55px',
            'index'        => 'id',
            'filter'       => false,
            'sortable'     => false,
            'frame_callback' => [$this, 'callbackColumnAction']
        ]);
    }

    protected function _prepareLayout()
    {
        $this->setChild(
            'refresh_button',
            $this->createBlock('Magento\Button')
                             ->setData([
                                   'id'        => 'productTaxCode_template_refresh_btn',
                                   'label'     => $this->__('Refresh'),
                                   'class'     => 'action primary',
                                   'onclick'   => "ListingGridHandlerObj.templateProductTaxCodeHandler.loadGrid()"
                             ])
        );

        return parent::_prepareLayout();
    }

    //########################################

    public function getRefreshButtonHtml()
    {
        return $this->getChildHtml('refresh_button');
    }

    //########################################

    public function getMainButtonsHtml()
    {
        return $this->getRefreshButtonHtml() . parent::getMainButtonsHtml();
    }

    //########################################

    public function callbackColumnTitle($value, $row, $column, $isExport)
    {
        $templateEditUrl = $this->getUrl('*/amazon_template_productTaxCode/edit', [
            'id'            => $row->getData('id'),
            'close_on_save' => true
        ]);

        $title = $this->getHelper('Data')->escapeHtml($value);

        return <<<HTML
<a target="_blank" href="{$templateEditUrl}">{$title}</a>
HTML;
    }

    public function callbackColumnAction($value, $row, $column, $isExport)
    {
        $assignText = $this->__('Assign');

        return <<<HTML
<a href="javascript:void(0)"
    class="assign-productTaxCode-template"
    templateProductTaxCodeId="{$value}">
    {$assignText}
</a>
HTML;
    }

    //########################################

    protected function _toHtml()
    {
        $this->js->add(
            <<<JS
ListingGridHandlerObj.templateProductTaxCodeHandler.newTemplateUrl='{$this->getNewTemplateProductTaxCodeUrl()}';
JS
        );

        return parent::_toHtml();
    }

    //########################################

    public function getGridUrl()
    {
        return $this->getUrl('*/amazon_listing_product_template_productTaxCode/viewGrid', [
            'products_ids' => implode(',', $this->getProductsIds()),
            '_current'     => true
        ]);
    }

    public function getRowUrl($row)
    {
        return false;
    }

    //########################################

    protected function setNoTemplatesText()
    {
        $messageTxt = $this->__('Product Tax Code Policies are not found.');
        $linkTitle = $this->__('Create New Product Tax Code Policy.');

        $message = <<<HTML
<p>{$messageTxt} <a href="javascript:void(0);"
    class="new-productTaxCode-template">{$linkTitle}</a>
</p>
HTML;

        $this->setEmptyText($message);
    }

    protected function getNewTemplateProductTaxCodeUrl()
    {
        return $this->getUrl('*/amazon_template_productTaxCode/new', [
            'close_on_save'  => true
        ]);
    }

    //########################################
}

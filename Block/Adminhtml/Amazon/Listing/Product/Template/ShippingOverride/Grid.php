<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Product\Template\ShippingOverride;

class Grid extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractGrid
{
    protected $marketplaceId;
    protected $productsIds;

    //########################################

    public function _construct()
    {
        parent::_construct();

        $this->setId('amazonTemplateShippingOverrideGrid');

        // Set default values
        // ---------------------------------------
        $this->setFilterVisibility(false);
        $this->setDefaultSort('id');
        $this->setDefaultDir('ASC');
        $this->setSaveParametersInSession(false);
        $this->setUseAjax(true);
        // ---------------------------------------
    }

    // ---------------------------------------

    /**
     * @return mixed
     */
    public function getMarketplaceId()
    {
        return $this->marketplaceId;
    }

    /**
     * @param mixed $marketplaceId
     */
    public function setMarketplaceId($marketplaceId)
    {
        $this->marketplaceId = $marketplaceId;
    }

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

        /** @var \Ess\M2ePro\Model\ResourceModel\Amazon\Template\ShippingOverride\Collection $collection */
        $collection = $this->activeRecordFactory->getObject('Amazon\Template\ShippingOverride')->getCollection();
        $collection->addFieldToFilter('marketplace_id', $this->getMarketplaceId());

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('title', array(
            'header'       => $this->__('Title'),
            'align'        => 'left',
            'type'         => 'text',
            'index'        => 'title',
            'filter'       => false,
            'sortable'     => false,
            'frame_callback' => array($this, 'callbackColumnTitle')
        ));

        $this->addColumn('action', array(
            'header'       => $this->__('Action'),
            'align'        => 'left',
            'type'         => 'number',
            'width'        => '55px',
            'index'        => 'id',
            'filter'       => false,
            'sortable'     => false,
            'frame_callback' => array($this, 'callbackColumnAction')
        ));
    }

    protected function _prepareLayout()
    {
        $shippingMode = $this->getRequest()->getParam('shipping_mode');
        $this->setChild('refresh_button',
            $this->createBlock('Magento\Button')
                ->setData(array(
                    'id' => 'shipping_override_template_refresh_btn',
                    'label'     => $this->__('Refresh'),
                    'class'     => 'action primary',
                    'onclick'   => "ListingGridHandlerObj.templateShippingHandler.loadGrid('{$shippingMode}')"
                ))
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
        $templateDescriptionEditUrl = $this->getUrl('*/amazon_template_shippingOverride/edit', array(
            'id' => $row->getData('id'),
            'close_on_save' => true
        ));

        $title = $this->getHelper('Data')->escapeHtml($value);

        return <<<HTML
<a target="_blank" href="{$templateDescriptionEditUrl}">{$title}</a>
HTML;

    }

    public function callbackColumnAction($value, $row, $column, $isExport)
    {
        $assignText = $this->__('Assign');

        return <<<HTML
<a href="javascript:void(0)"
    class="assign-shipping-template"
    templateShippingId="{$value}">
    {$assignText}
</a>
HTML;

    }

    //########################################

    protected function _toHtml()
    {
        $this->js->add(
            <<<JS
ListingGridHandlerObj.templateShippingHandler.newTemplateUrl='{$this->getNewTemplateShippingOverrideUrl()}';
JS
        );

        return parent::_toHtml();
    }

    //########################################

    public function getGridUrl()
    {
        return $this->getUrl('*/*/viewGrid', array(
            '_current' => true,
            'shipping_mode' => \Ess\M2ePro\Model\Amazon\Account::SHIPPING_MODE_OVERRIDE,
            '_query' => array(
                'marketplace_id' => $this->getMarketplaceId()
            ),
            'products_ids' => implode(',', $this->getProductsIds()),
        ));
    }

    public function getRowUrl($row)
    {
        return false;
    }

    //########################################

    protected function setNoTemplatesText()
    {
        $messageTxt = $this->__('Shipping Override Policies are not found for current Marketplace.');
        $linkTitle = $this->__('Create New Shipping Override Policy.');

        $message = <<<HTML
<p>{$messageTxt} <a href="javascript:void(0);"
    class="new-shipping-template">{$linkTitle}</a>
</p>
HTML;

        $this->setEmptyText($message);
    }

    protected function getNewTemplateShippingOverrideUrl()
    {
        return $this->getUrl('*/amazon_template_shippingOverride/new', array(
            'marketplace_id' => $this->getMarketplaceId(),
            'close_on_save'  => true
        ));
    }

    //########################################
}
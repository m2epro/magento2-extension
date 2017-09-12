<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\View\Settings\Motors\View\Group;

class Grid extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractGrid
{
    private $listingProductId;
    private $listingProduct;

    private $motorsType;

    //########################################

    public function _construct()
    {
        parent::_construct();

        $this->setId('ebayMotorViewGroupGrid');

        // Set default values
        //------------------------------
        $this->setDefaultSort('id');
        $this->setDefaultDir('ASC');
        $this->setSaveParametersInSession(false);
        $this->setUseAjax(true);
        $this->setPagerVisibility(false);
        $this->setDefaultLimit(false);
        //------------------------------
    }

    //------------------------------

    protected function _prepareCollection()
    {
        $motorsHelper = $this->getHelper('Component\Ebay\Motors');

        $attributeValue = $this->getListingProduct()->getMagentoProduct()->getAttributeValue(
            $motorsHelper->getAttribute($this->getMotorsType())
        );

        $motorsData = $motorsHelper->parseAttributeValue($attributeValue);

        $collection = $this->activeRecordFactory->getObject('Ebay\Motor\Group')->getCollection();
        $collection->getSelect()->where('id IN (?)', $motorsData['groups']);

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
            'frame_callback' => [$this, 'callbackColumnTitle']
        ]);
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('id');
        $this->getMassactionBlock()->setUseSelectAll(false);
        $this->setMassactionIdFieldOnlyIndexValue(true);

        // Set mass-action
        //--------------------------------
        $this->getMassactionBlock()->addItem('removeGroup', [
            'label'   => $this->__('Remove'),
            'url'     => '',
            'confirm' => $this->__('Are you sure?')
        ]);
        //--------------------------------

        return parent::_prepareMassaction();
    }

    //########################################

    public function callbackColumnTitle($value, $row, $column, $isExport)
    {
        return $value;
    }

    //########################################

    protected function _toHtml()
    {
        if (!$this->canDisplayContainer()) {

            $this->js->add(<<<JS
    EbayListingViewSettingsMotorsViewGroupGridObj.afterInitPage();
JS
            );

            return parent::_toHtml();
        }

        $this->js->add(<<<JS
    require([
        'M2ePro/Ebay/Listing/View/Settings/Motors/View/Group/Grid'
    ], function() {
        EbayListingViewSettingsMotorsViewGroupGridObj = new EbayListingViewSettingsMotorsViewGroupGrid(
            '{$this->getId()}',
            '{$this->getListingProductId()}'
        );
        EbayListingViewSettingsMotorsViewGroupGridObj.afterInitPage();
    });
JS
        );

        return parent::_toHtml();
    }

    //########################################

    public function getGridUrl()
    {
        return $this->getUrl('*/ebay_listing_settings_motors/viewGroupGrid', [
            '_current' => true
        ]);
    }

    public function getRowUrl($row)
    {
        return false;
    }

    //########################################

    public function setMotorsType($motorsType)
    {
        $this->motorsType = $motorsType;
    }

    public function getMotorsType()
    {
        if (is_null($this->motorsType)) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Motors type not set.');
        }

        return $this->motorsType;
    }

    //########################################

    public function getItemsColumnTitle()
    {
        if ($this->getHelper('Component\Ebay\Motors')->isTypeBasedOnEpids($this->getMotorsType())) {
            return $this->__('ePID(s)');
        }

        return $this->__('kType(s)');
    }

    //########################################

    /**
     * @return null
     */
    public function getListingProductId()
    {
        return $this->listingProductId;
    }

    /**
     * @param null $listingProductId
     */
    public function setListingProductId($listingProductId)
    {
        $this->listingProductId = $listingProductId;
    }

    public function getListingProduct()
    {
        if (is_null($this->listingProduct)) {
            $this->listingProduct = $this->parentFactory->getObjectLoaded(
                \Ess\M2ePro\Helper\Component\Ebay::NICK , 'Listing\Product', $this->getListingProductId()
            );
        }

        return $this->listingProduct;
    }

    //########################################
}
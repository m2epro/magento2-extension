<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\View\Settings\Motors\Add\Group;

class Grid extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractGrid
{
    private $motorsType;

    //########################################

    public function _construct()
    {
        parent::_construct();

        $this->setId('ebayMotorAddTabGroupGrid');

        // Set default values
        //------------------------------
        $this->setDefaultSort('id');
        $this->setDefaultDir('ASC');
        $this->setSaveParametersInSession(false);
        $this->setUseAjax(true);
        //------------------------------
    }

    //------------------------------

    protected function _prepareCollection()
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Ebay\Motor\Group\Collection $collection */
        $collection = $this->activeRecordFactory->getObject('Ebay\Motor\Group')->getCollection();
        $collection->addFieldToFilter('type', ['=' => $this->getMotorsType()]);

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

        $this->addColumn('mode', [
            'header'       => $this->__('Type'),
            'width'        => '150px',
            'align'        => 'left',
            'type'         => 'options',
            'index'        => 'mode',
            'filter_index' => 'mode',
            'options' => [
                \Ess\M2ePro\Model\Ebay\Motor\Group::MODE_ITEM    => $this->getItemsColumnTitle(),
                \Ess\M2ePro\Model\Ebay\Motor\Group::MODE_FILTER  => $this->__('Filters'),
            ],
            'frame_callback' => [$this, 'callbackColumnMode']
        ]);

        $this->addColumn('items', [
            'header'       => $this->__('Amount'),
            'width'        => '60px',
            'align'        => 'center',
            'type'         => 'text',
            'sortable'     => false,
            'filter'       => false,
            'index'        => 'items_data',
            'frame_callback' => [$this, 'callbackColumnItems']
        ]);
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('id');
        $this->setMassactionIdFieldOnlyIndexValue(true);

        // Set mass-action
        //--------------------------------
        $this->getMassactionBlock()->addItem('select', [
            'label'   => $this->__('Select'),
            'url'     => '',
            'confirm' => $this->__('Are you sure?')
        ]);

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

    public function callbackColumnMode($value, $row, $column, $isExport)
    {
        /** @var \Ess\M2ePro\Model\Ebay\Motor\Group $row */

        if ($row->isModeItem()) {
            return $this->getItemsColumnTitle();
        }

        if ($row->isModeFilter()) {
            return $this->__('Filters');
        }

        return $value;
    }

    public function callbackColumnItems($value, $row, $column, $isExport)
    {
        /** @var \Ess\M2ePro\Model\Ebay\Motor\Group $row */

        if ($row->isModeItem()) {
            $itemsCount = count($row->getItems());
            $title = $this->getHelper('Data')->escapeHtml(
                $this->__('View Group '.$this->getItemsColumnTitle())
            );
        } else {
            $itemsCount = count($row->getFiltersIds());
            $title = $this->getHelper('Data')->escapeHtml(
                $this->__('View Group Filters')
            );
        }

        return <<<HTML
<a onclick="EbayListingViewSettingsMotorsAddGroupGridObj.viewGroupContentPopup({$row->getId()}, '{$title}');"
    href="javascript:void(0)">
    {$itemsCount}
</a>
HTML;
    }

    //########################################

    protected function _toHtml()
    {

        if (!$this->canDisplayContainer()) {

            $this->js->add(<<<JS
    EbayListingViewSettingsMotorsAddGroupGridObj.afterInitPage();
JS
            );

            return parent::_toHtml();
        }

        $this->js->add(<<<JS
    require([
        'M2ePro/Ebay/Listing/View/Settings/Motors/Add/Group/Grid'
    ], function() {

        EbayListingViewSettingsMotorsAddGroupGridObj = new EbayListingViewSettingsMotorsAddGroupGrid(
            '{$this->getId()}'
        );
        EbayListingViewSettingsMotorsAddGroupGridObj.afterInitPage();

    });
JS
        );

        return parent::_toHtml();
    }

    //########################################

    public function getGridUrl()
    {
        return $this->getUrl('*/ebay_listing_settings_motors/addGroupGrid', [
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
}
<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\View\Settings\Motors\Add\Item;

use \Ess\M2ePro\Helper\Component\Ebay\Motors;

abstract class Grid extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractGrid
{
    private $listingId  = NULL;
    private $motorsType = NULL;

    //########################################

    public function _construct()
    {
        parent::_construct();

        $this->_isExport = true;

        // Initialization block
        //------------------------------
        $motorsType = $this->getHelper('Component\Ebay\Motors')->getIdentifierKey($this->getMotorsType());
        $this->setId('ebayMotor'.$motorsType.'Grid');
        //------------------------------

        // Set default values
        //------------------------------
        $this->setDefaultSort('make');
        $this->setDefaultDir('ASC');
        $this->setSaveParametersInSession(false);
        $this->setUseAjax(true);
        //------------------------------
    }

    //########################################

    public function setListingId($marketplaceId)
    {
        $this->listingId = $marketplaceId;
        return $this;
    }

    public function getListingId()
    {
        return $this->listingId;
    }

    public function setMotorsType($motorsType)
    {
        $this->motorsType = $motorsType;
        return $this;
    }

    public function getMotorsType()
    {
        return $this->motorsType;
    }

    //########################################

    protected function _prepareMassaction()
    {
        $typeIdentifier = $this->getHelper('Component\Ebay\Motors')->getIdentifierKey(
            $this->getMotorsType()
        );

        // Set massaction identifiers
        //--------------------------------
        $this->setMassactionIdField($typeIdentifier);
        $this->getMassactionBlock()->setFormFieldName($typeIdentifier);
        //--------------------------------

        // Set mass-action
        //--------------------------------
        $this->getMassactionBlock()->addItem('select', [
            'label'   => $this->__('Select'),
            'url'     => '',
            'confirm' => $this->__('Are you sure?')
        ]);

        $this->getMassactionBlock()->addItem('setNote', [
            'label'   => $this->__('Set Note'),
            'url'     => '',
            'confirm' => $this->__('Are you sure?')
        ]);

        $this->getMassactionBlock()->addItem('resetNote', [
            'label'   => $this->__('Reset Note'),
            'url'     => '',
            'confirm' => $this->__('Are you sure?')
        ]);

        $this->getMassactionBlock()->addItem('saveAsGroup', [
            'label'   => $this->__('Save As Group'),
            'url'     => '',
            'confirm' => $this->__('Are you sure?')
        ]);
        //--------------------------------

        return parent::_prepareMassaction();
    }

    //########################################

    public function callbackColumnIdentifier($value, $row, $column, $isExport)
    {
        $type = $this->getMotorsType();

        $idKey = $this->getHelper('Component\Ebay\Motors')->getIdentifierKey($type);

        $removeTitle = $this->__('Remove this record.');

        $removeCustomRecordHtml = '';
        if (isset($row['is_custom']) && $row['is_custom']) {
            $removeCustomRecordHtml = <<<HTML
<a href="javascript:void(0);"
   class="ebay-listing-view-icon ebay-listing-view-remove" style="font-size: 11px;"
   onclick="EbayListingViewSettingsMotorsObj.removeCustomMotorsRecord('{$type}', '{$row[$idKey]}');"
   align="center" title="{$removeTitle}"></a>
HTML;
        }

        $noteWord = $this->__('Note');

        return <<<HTML

{$value} {$removeCustomRecordHtml}
<br/>
<br/>
<div id="note_{$row[$idKey]}" style="color: gray; display: none;">
    <span style="text-decoration: underline">{$noteWord}</span>: <br/>
    <span class="note-view"></span>
</div>

HTML;
    }

    public function callbackNullableColumn($value, $row, $column, $isExport)
    {
        return $value ? $value : '--';
    }

    //########################################

    public function getGridUrl()
    {
        return $this->getUrl(
            '*/ebay_listing_settings_motors/addItemGrid', [
                '_current' => true,
                'listing_id' => $this->getListingId()
            ]
        );
    }

    public function getRowUrl($row)
    {
        $this->getGrgetGridIdsJson();

        return false;
    }

    //########################################

    protected function _prepareLayout()
    {
        //------------------------------
        $data = [
            'id'      => 'save_filter_btn',
            'label'   => $this->__('Save Filter'),
            'class'   => 'action-primary',
            'onclick' => 'EbayListingViewSettingsMotorsAddItemGridObj.saveFilter()'
        ];
        $saveFilterBtn = $this->createBlock('Magento\Button')->setData($data);
        $this->setChild('save_filter', $saveFilterBtn);
        //------------------------------

        return parent::_prepareLayout();
    }

    //########################################

    public function getSaveFilterButtonHtml()
    {
        return $this->getChildHtml('save_filter');
    }

    //########################################

    public function getMainButtonsHtml()
    {
        return $this->getSaveFilterButtonHtml() .
            parent::getMainButtonsHtml();
    }

    //########################################

    protected function _toHtml()
    {
        if (!$this->canDisplayContainer()) {

            $this->js->add(<<<JS
                EbayListingViewSettingsMotorsAddItemGridObj.afterInitPage();
JS
            );

            return parent::_toHtml();
        }

        $this->css->add(<<<CSS
    a.remove-custom-created-record-link {
        display: inline-block;
        width: 8px;
        height: 9px;
        margin-left: 3px;
        background-position: center;
        background-repeat: no-repeat;
        background-image: url("{$this->getSkinUrl('M2ePro/images/delete.png')}");
    }
CSS
        );

        $this->jsTranslator->addTranslations([
            'It is impossible to select all the items.' => $this->__(<<<HTML
It is impossible to select such a large number of items due to technical reasons.
You can add up to <b>%limitation%</b> Compatible Vehicles at once.<br/>
Select <b>%limitation%</b> or fewer records and submit the <i>Select</i> action.
Then <i>Add</i> or <i>Override</i> selected items according to your needs.
<br><br>
You are able to add more than <b>%limitation%</b> Compatible Vehicles at once by the following methods:
<ul style="margin-left: 35px; padding-top: 10px;">
    <li>
        <b>use saved Filter</b> - filter the records by required parameters and click on <i>Save Filter</i> button.
        On the <i>Filters</i> tab, choose appropriate saved Filter, submit the <i>Select</i> action,
        then <i>Add/Override</i> all the Compatible Vehicles that suit your filtering settings at once.
    </li>
    <br/>
    <li>
        <b>use saved Group</b> - select <b>%limitation%</b> or fewer records and submit the <i>Save as Group</i> action.
        Create as many Groups as you need. On the <i>Group</i> tab, choose appropriate saved Groups,
        submit the <i>Select</i> action, then <i>Add/Override</i> all the Compatible Vehicles belonged to
        these Groups at once.
    </li>
</ul>
HTML
                ,
                Motors::MAX_ITEMS_COUNT_FOR_ATTRIBUTE
            ),
        ]);

        $this->jsPhp->addConstants(
            $this->getHelper('Data')->getClassConstants('\Ess\M2ePro\Helper\Component\Ebay\Motors')
        );

        $this->js->add(<<<JS
    require([
        'M2ePro/Ebay/Listing/View/Settings/Motors/Add/Item/Grid'
    ], function() {

        EbayListingViewSettingsMotorsAddItemGridObj = new EbayListingViewSettingsMotorsAddItemGrid('{$this->getId()}');
        EbayListingViewSettingsMotorsAddItemGridObj.afterInitPage();

        $('save_filter_btn').addClassName('disabled');

    });
JS
        );

        return parent::_toHtml();
    }

    public function getEmptyText()
    {
        return $this->__(<<<HTML
    No records found. You can <a href="javascript:void(0)"
    onclick="EbayListingViewSettingsMotorsObj.openAddRecordPopup()">add Custom Compatible Vehicles</a> manually
    or through the <a target="_blank" href="%settings_link%">Import Tool</a>.
HTML
            ,
            $this->getUrl('*/ebay_settings/index', [
                'active_tab' => \Ess\M2ePro\Block\Adminhtml\Ebay\Settings\Tabs::TAB_ID_MOTORS
            ])
        );
    }

    //########################################

    public function getItemTitle()
    {
        if ($this->getHelper('Component\Ebay\Motors')->isTypeBasedOnEpids($this->getMotorsType())) {
            return $this->__('ePID(s)');
        }

        return $this->__('kType(s)');
    }

    //########################################
}
<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Listing\Log;

use \Ess\M2ePro\Block\Adminhtml\Log\Grid\AbstractGrid;

abstract class Grid extends AbstractGrid
{
    protected $viewComponentHelper = NULL;

    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialize view
        // ---------------------------------------
        $view = $this->getHelper('View')->getCurrentView();
        $this->viewComponentHelper = $this->getHelper('View')->getComponentHelper($view);
        // ---------------------------------------

        $channel = $this->getRequest()->getParam('channel');

        // Initialization block
        // ---------------------------------------
        $this->setId($view . ucfirst($channel) . 'ListingLogGrid' . $this->getEntityId());
        // ---------------------------------------

        // Set default values
        // ---------------------------------------
        $this->setDefaultSort('create_date');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
        // ---------------------------------------
    }

    //########################################

    protected function _prepareCollection()
    {
        // Get collection logs
        // ---------------------------------------
        $collection = $this->activeRecordFactory->getObject('Listing\Log')->getCollection();
        // ---------------------------------------

        // Set listing filter
        // ---------------------------------------
        if ($this->getEntityId()) {
            if ($this->isListingProductLog() && $this->getListingProduct()->isComponentModeAmazon() &&
                $this->getListingProduct()->getChildObject()->getVariationManager()->isRelationParentType()) {
                $collection->addFieldToFilter(
                    array(
                        self::LISTING_PRODUCT_ID_FIELD,
                        self::LISTING_PARENT_PRODUCT_ID_FIELD
                    ),
                    array(
                        array(
                            'attribute' => self::LISTING_PRODUCT_ID_FIELD,
                            'eq' => $this->getEntityId()
                        ),
                        array(
                            'attribute' => self::LISTING_PARENT_PRODUCT_ID_FIELD,
                            'eq' => $this->getEntityId()
                        )
                    )
                );
            } else {
                $collection->addFieldToFilter($this->getEntityField(), $this->getEntityId());
            }
        }
        // ---------------------------------------

        // prepare components
        // ---------------------------------------
        $component = $this->getRequest()->getParam('channel', false);

        if (!$component) {

            $component = $this->getComponentMode();
        }

        if (!empty($component) && $component != \Ess\M2ePro\Block\Adminhtml\Amazon\Log\Tabs::CHANNEL_ID_ALL) {
            $collection->getSelect()->where('main_table.component_mode = ?', $component);

            $accountId = (int)$this->getRequest()->getParam($component.'Account', false);
            $marketplaceId = (int)$this->getRequest()->getParam($component.'Marketplace', false);

            if ($accountId || $marketplaceId) {
                $collection->join(
                    ['l' => $this->activeRecordFactory->getObject('Listing')->getResource()->getMainTable()],
                    '(l.id = main_table.listing_id)',
                    [
                        'marketplace_id'=>'marketplace_id',
                        'account_id'=>'account_id',
                    ]
                );
            }

            if ($accountId) {
                $collection->getSelect()->where('l.account_id = ?', $accountId);
            }

            if ($marketplaceId) {
                $collection->getSelect()->where('l.marketplace_id = ?', $marketplaceId);
            }

        } else {
            $collection->getSelect()->where('main_table.component_mode IS NULL');
        }
        // ---------------------------------------

        // we need sort by id also, because create_date may be same for some adjustment entries
        // ---------------------------------------
        if ($this->getRequest()->getParam('sort', 'create_date') == 'create_date') {
            $collection->setOrder('id', $this->getRequest()->getParam('dir', 'DESC'));
        }
        // ---------------------------------------

        // Set collection to grid
        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('create_date', array(
            'header'    => $this->__('Creation Date'),
            'align'     => 'left',
            'type'      => 'datetime',
            'index'     => 'create_date'
        ));

        $this->addColumn('action', array(
            'header'    => $this->__('Action'),
            'align'     => 'left',
            'type'      => 'options',
            'index'     => 'action',
            'sortable'  => false,
            'filter_index' => 'main_table.action',
            'options' => $this->getActionTitles()
        ));

        if (!$this->getEntityId()) {
            $this->addColumn('listing_title', array(
                'header'    => $this->__('Listing Title / ID'),
                'align'     => 'left',
                'type'      => 'text',
                'index'     => 'listing_title',
                'filter_index' => 'main_table.listing_title',
                'frame_callback' => array($this, 'callbackColumnListingTitleID'),
                'filter_condition_callback' => array($this, 'callbackFilterListingTitleID')
            ));
        }

        if (!$this->isListingProductLog()) {
            $this->addColumn('product_title', array(
                'header' => $this->__('Product Title / ID'),
                'align' => 'left',
                'type' => 'text',
                'index' => 'product_title',
                'filter_index' => 'main_table.product_title',
                'frame_callback' => array($this, 'callbackColumnProductTitleID'),
                'filter_condition_callback' => array($this, 'callbackFilterProductTitleID')
            ));
        }

        if ($this->isListingProductLog() && $this->getListingProduct()->isComponentModeAmazon() &&
            ($this->getListingProduct()->getChildObject()->getVariationManager()->isRelationParentType() ||
                $this->getListingProduct()->getChildObject()->getVariationManager()->isRelationChildType() ||
                $this->getListingProduct()->getChildObject()->getVariationManager()->isIndividualType())) {

            $this->addColumn('attributes', array(
                'header' => $this->__('Variation'),
                'align' => 'left',
                'index' => 'additional_data',
                'sortable'  => false,
                'filter_index' => 'main_table.additional_data',
                'frame_callback' => array($this, 'callbackColumnAttributes'),
                'filter_condition_callback' => array($this, 'callbackFilterAttributes')
            ));
        }

        $this->addColumn('description', array(
            'header'    => $this->__('Description'),
            'align'     => 'left',
            'type'      => 'text',
            'index'     => 'description',
            'filter_index' => 'main_table.description',
            'frame_callback' => array($this, 'callbackDescription')
        ));

        $this->addColumn('initiator', array(
            'header'=> $this->__('Run Mode'),
            'index' => 'initiator',
            'align' => 'right',
            'type'  => 'options',
            'sortable'  => false,
            'options' => $this->_getLogInitiatorList(),
            'frame_callback' => array($this, 'callbackColumnInitiator')
        ));

        $this->addColumn('type', array(
            'header'=> $this->__('Type'),
            'index' => 'type',
            'align' => 'right',
            'type'  => 'options',
            'sortable'  => false,
            'options' => $this->_getLogTypeList(),
            'frame_callback' => array($this, 'callbackColumnType')
        ));

        return parent::_prepareColumns();
    }

    protected function _prepareMassaction()
    {
        // Set massaction identifiers
        // ---------------------------------------
        $this->setMassactionIdField('id');
        $this->getMassactionBlock()->setFormFieldName('ids');
        // ---------------------------------------
    }

    //########################################

    abstract protected function getComponentMode();

    //########################################

    public function callbackColumnListingTitleID($value, $row, $column, $isExport)
    {
        if (strlen($value) > 50) {
            $value = substr($value, 0, 50) . '...';
        }

        $value = $this->getHelper('Data')->escapeHtml($value);

        if ($row->getData('listing_id')) {

            $url = $this->getUrl(
                '*/'.$row->getData('component_mode').'_listing/view',
                array('id' => $row->getData('listing_id'))
            );

            $value = '<a target="_blank" href="'.$url.'">' .
                $value .
                '</a><br/>ID: '.$row->getData('listing_id');
        }

        return $value;
    }

    public function callbackColumnProductTitleID($value, $row, $column, $isExport)
    {
        if (!$row->getData('product_id')) {
            return $value;
        }

        $url = $this->getUrl('catalog/product/edit', array('id' => $row->getData('product_id')));
        $value = '<a target="_blank" href="'.$url.'" target="_blank">'.
            $this->getHelper('Data')->escapeHtml($value).
            '</a><br/>ID: '.$row->getData('product_id');

        $additionalData = json_decode($row->getData('additional_data'), true);
        if (empty($additionalData['variation_options'])) {
            return $value;
        }

        $value .= '<div style="font-size: 11px; color: grey;">';
        foreach ($additionalData['variation_options'] as $attribute => $option) {
            !$option && $option = '--';
            $value .= '<strong>'.
                $this->getHelper('Data')->escapeHtml($attribute) .
                '</strong>:&nbsp;'.
                $this->getHelper('Data')->escapeHtml($option) . '<br/>';
        }
        $value .= '</div>';

        return $value;
    }

    public function callbackColumnAttributes($value, $row, $column, $isExport)
    {
        $additionalData = json_decode($row->getData('additional_data'), true);
        if (empty($additionalData['variation_options'])) {
            return '';
        }

        $result = '<div style="font-size: 11px; color: grey;">';
        foreach ($additionalData['variation_options'] as $attribute => $option) {
            !$option && $option = '--';
            $result .= '<strong>'.
                $this->getHelper('Data')->escapeHtml($attribute) .
                '</strong>:&nbsp;'.
                $this->getHelper('Data')->escapeHtml($option) . '<br/>';
        }
        $result .= '</div>';

        return $result;
    }

    //########################################

    protected function callbackFilterListingTitleID($collection, $column)
    {
        $value = $column->getFilter()->getValue();

        if ($value == null) {
            return;
        }

        $where = 'listing_title LIKE ' . $collection->getSelect()->getAdapter()->quote('%'. $value .'%');
        is_numeric($value) && $where .= ' OR listing_id = ' . $value;

        $collection->getSelect()->where($where);
    }

    protected function callbackFilterProductTitleID($collection, $column)
    {
        $value = $column->getFilter()->getValue();

        if ($value == null) {
            return;
        }

        $where = 'product_title LIKE ' . $collection->getSelect()->getAdapter()->quote('%'. $value .'%');
        is_numeric($value) && $where .= ' OR product_id = ' . $value;

        $collection->getSelect()->where($where);
    }

    protected function callbackFilterAttributes($collection, $column)
    {
        $value = $column->getFilter()->getValue();

        if ($value == null) {
            return;
        }

        $collection->getSelect()->where('additional_data LIKE ?', '%'. $value .'%');
    }

    //########################################

    public function getGridUrl()
    {
        return $this->getUrl('*/*/'.$this->getActionName(), array(
            '_current'=>true,
            'channel' => $this->getRequest()->getParam('channel')
        ));
    }

    public function getRowUrl($row)
    {
        return false;
    }

    //########################################

    abstract protected function getActionTitles();

    //########################################
}
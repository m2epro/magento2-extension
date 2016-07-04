<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Listing\Other\Log;

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
        $this->setId($view . ucfirst($channel) . 'ListingOtherLogGrid' . $this->getEntityId());
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
        $listingData = $this->getHelper('Data\GlobalData')->getValue('temp_data');

        // Get collection logs
        // ---------------------------------------
        $collection = $this->activeRecordFactory->getObject('Listing\Other\Log')->getCollection();
        // ---------------------------------------

        // Join amazon_listings_table
        // ---------------------------------------
        $collection->getSelect()
            ->joinLeft(array('lo' => $this->activeRecordFactory->getObject('Listing\Other')
                ->getResource()->getMainTable()),
                       '(`main_table`.listing_other_id = `lo`.id)',
                       array(
                           'account_id'     => 'lo.account_id',
                           'marketplace_id' => 'lo.marketplace_id'
                       )
            )
            ->joinLeft(array('ea' => $this->activeRecordFactory->getObject('Ebay\Account')
                ->getResource()->getMainTable()),
                             '(`lo`.account_id = `ea`.account_id)',
                             array('account_mode' => 'ea.mode')
            );
        // ---------------------------------------

        // Set listing filter
        // ---------------------------------------
        if (isset($listingData['id'])) {
            $collection->addFieldToFilter('main_table.listing_other_id', $listingData['id']);
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

            if ($accountId) {
                $collection->getSelect()->where('lo.account_id = ?', $accountId);
            }

            if ($marketplaceId) {
                $collection->getSelect()->where('lo.marketplace_id = ?', $marketplaceId);
            }

        } else {
            $collection->getSelect()->where('main_table.component_mode IS NULL');
        }
        // ---------------------------------------

        // we need sort by id also, because create_date may be same for some adjacents entries
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
        $columnTitles = $this->getColumnTitles();

        $this->addColumn('create_date', array(
            'header'    => $columnTitles['create_date'],
            'align'     => 'left',
            'type'      => 'datetime',
//            'format'    => Mage::app()->getLocale()->getDateTimeFormat(Mage_Core_Model_Locale::FORMAT_TYPE_MEDIUM),
            'width'     => '150px',
            'index'     => 'create_date',
            'filter_index' => 'main_table.create_date',
        ));

        $this->addColumn('action', array(
            'header'    => $columnTitles['action'],
            'align'     => 'left',
            'type'      => 'options',
            'index'     => 'action',
            'sortable'  => false,
            'filter_index' => 'main_table.action',
            'options' => $this->getActionTitles(),
        ));

        $this->addColumn('identifier', array(
            'header' => $columnTitles['identifier'],
            'align'  => 'left',
            'type'   => 'text',
            'index'  => 'identifier',
            'filter_index' => 'main_table.identifier',
            'frame_callback' => array($this, 'callbackColumnIdentifier')
        ));

        $this->addColumn('title', array(
            'header'    => $columnTitles['title'],
            'align'     => 'left',
            'type'      => 'text',
            'index'     => 'title',
            'filter_index' => 'main_table.title',
            'frame_callback' => array($this, 'callbackColumnTitle')
        ));

        $this->addColumn('description', array(
            'header'    => $columnTitles['description'],
            'align'     => 'left',
            'type'      => 'text',
            'index'     => 'description',
            'filter_index' => 'main_table.description',
            'frame_callback' => array($this, 'callbackDescription')
        ));

        $this->addColumn('initiator', array(
            'header'=> $columnTitles['initiator'],
            'index' => 'initiator',
            'align' => 'right',
            'type'  => 'options',
            'sortable'  => false,
            'options' => $this->_getLogInitiatorList(),
            'frame_callback' => array($this, 'callbackColumnInitiator')
        ));

        $this->addColumn('type', array(
            'header'=> $columnTitles['type'],
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

    public function callbackColumnIdentifier($value, $row, $column, $isExport)
    {
        $identifier = $this->__('N/A');

        if (is_null($value) || $value === '') {
            return $identifier;
        }

        $accountMode   = $row->getData('account_mode');
        $marketplaceId = $row->getData('marketplace_id');

        switch ($row->getData('component_mode')) {
            case \Ess\M2ePro\Helper\Component\Ebay::NICK:
                $url = $this->getHelper('Component\Ebay')->getItemUrl($value, $accountMode, $marketplaceId);
                $identifier = '<a href="' . $url . '" target="_blank">' . $value . '</a>';
                break;

            case \Ess\M2ePro\Helper\Component\Amazon::NICK:
                $url = $this->getHelper('Component\Amazon')->getItemUrl($value, $marketplaceId);
                $identifier = '<a href="' . $url . '" target="_blank">' . $value . '</a>';
                break;

//            todo NOT SUPPORTED FEATURES
//            case \Ess\M2ePro\Helper\Component\Buy::NICK:
//                $url = Mage::helper('M2ePro/Component_Buy')->getItemUrl($value);
//                $identifier = '<a href="' . $url . '" target="_blank">' . $value . '</a>';
//                break;
        }

        return $identifier;
    }

    public function callbackColumnTitle($value, $row, $column, $isExport)
    {
        if (is_null($value) || $value === '') {
            return $this->__('N/A');
        }

        return '<span>' . $this->getHelper('Data')->escapeHtml($value) . '</span>';
    }

    //########################################

    public function getGridUrl()
    {
        return $this->getUrl('*/*/grid', array(
            '_current'=>true,
            'channel' => $this->getRequest()->getParam('channel')
        ));
    }

    public function getRowUrl($row)
    {
        return false;
    }

    //########################################

    abstract protected function getColumnTitles();

    //########################################

    abstract protected function getActionTitles();

    //########################################
}
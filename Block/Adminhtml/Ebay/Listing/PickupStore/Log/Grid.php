<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\PickupStore\Log;

class Grid extends \Ess\M2ePro\Block\Adminhtml\Log\AbstractGrid
{
    protected $listingProductPickupStoreStateId;

    //########################################

    public function _construct()
    {
        parent::_construct();

        $this->setId('ebayListingPickupStoreLogGrid');

        // Set default values
        // ---------------------------------------
        $this->setDefaultSort('id');
        $this->setDefaultDir('ASC');
        $this->setSaveParametersInSession(false);
        $this->setUseAjax(true);
        // ---------------------------------------

        $this->listingProductPickupStoreStateId = (int)$this->getRequest()->getParam(
            'listing_product_pickup_store_state', 0
        );
        $this->isAjax = $this->getHelper('Data')->jsonEncode($this->getRequest()->isXmlHttpRequest());
    }

    //########################################

    protected function _prepareCollection()
    {
        $pickupStoreCollection = $this->activeRecordFactory->getObject('Ebay\Account\PickupStore\Log')->getCollection();
        $pickupStoreCollection->addFieldToFilter(
            'account_pickup_store_state_id', $this->listingProductPickupStoreStateId
        );

        // Set collection to grid
        $this->setCollection($pickupStoreCollection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('create_date', [
            'header'    => $this->__('Creation Date'),
            'align'     => 'left',
            'type'      => 'datetime',
            'index'     => 'create_date',
            'format' => \IntlDateFormatter::MEDIUM,
            'filter_time' => true,
        ]);

        $this->addColumn('action', [
            'header'    => $this->__('Action'),
            'align'     => 'left',
            'width'     => '150px',
            'type'      => 'options',
            'index'     => 'action',
            'sortable'  => false,
            'filter_index' => 'action',
            'options' => $this->getActionTitles()
        ]);

        $this->addColumn('location_id', [
            'header'    => $this->__('Name / Location ID'),
            'align'     => 'left',
            'type'      => 'text',
            'index'     => 'location_id',
            'frame_callback' => [$this, 'callbackColumnTitle'],
            'filter_condition_callback' => [$this, 'callbackFilterTitle']
        ]);

        $this->addColumn('description', [
            'header'    => $this->__('Message'),
            'align'     => 'left',
            'type'      => 'text',
            'index'     => 'description',
            'filter_index' => 'main_table.description',
            'frame_callback' => [$this, 'callbackDescription']
        ]);

        $this->addColumn('type', [
            'header'=> $this->__('Type'),
            'width' => '80px',
            'index' => 'type',
            'align' => 'right',
            'type'  => 'options',
            'sortable'  => false,
            'options' => $this->_getLogTypeList(),
            'frame_callback' => [$this, 'callbackColumnType']
        ]);

        return parent::_prepareColumns();
    }

    //########################################

    public function callbackColumnTitle($value, $row, $column, $isExport)
    {
        $name = $row->getData('location_title');
        $locationId = $row->getData('location_id');

        $locationIdLabel = $this->__('Location ID');

        return "{$name} <br/>
                <strong>{$locationIdLabel}</strong>: {$locationId} <br/>";
    }

    // ---------------------------------------

    protected function callbackFilterTitle($collection, $column)
    {
        $value = $column->getFilter()->getValue();

        if ($value == null) {
            return;
        }

        $collection->getSelect()->where(
            "main_table.location_title LIKE '%{$value}%'
            OR main_table.location_id LIKE '%{$value}%'"
        );
    }

    //########################################

    protected function getActionTitles()
    {
        return $this->activeRecordFactory->getObject('Ebay\Account\PickupStore\Log')->getActionsTitles();
    }

    //########################################

    public function getGridUrl()
    {
        return $this->getUrl('*/*/logGrid', [
            'listing_product_pickup_store_state' => $this->listingProductPickupStoreStateId
        ]);
    }

    public function getRowUrl($row)
    {
        return false;
    }

    //########################################
}
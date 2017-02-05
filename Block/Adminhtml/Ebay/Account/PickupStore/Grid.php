<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Account\PickupStore;

use Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractGrid;

class Grid extends AbstractGrid
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        $this->css->addFile('account/grid.css');

        // Initialization block
        // ---------------------------------------
        $this->setId('ebayAccountPickupStoreGrid');
        // ---------------------------------------

        // Set default values
        // ---------------------------------------
        $this->setDefaultSort('id');
        $this->setDefaultDir('ASC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
        // ---------------------------------------
    }

    //########################################

    protected function _prepareCollection()
    {
        $pickupStoreCollection = $this->activeRecordFactory->getObject('Ebay\Account\PickupStore')->getCollection();
        $pickupStoreCollection->getSelect()->join(
            ['mm' => $this->activeRecordFactory->getObject('Marketplace')->getResource()->getMainTable()],
            'main_table.marketplace_id = mm.id',
            ['marketplace_title' => 'title', 'marketplace_id' => 'id']
        );
        $pickupStoreCollection->addFieldToFilter('mm.component_mode', \Ess\M2ePro\Helper\Component\Ebay::NICK);

        $pickupStoreCollection->getSelect()->join(
            ['ma' => $this->activeRecordFactory->getObject('Account')->getResource()->getMainTable()],
            'main_table.account_id = ma.id',
            ['account_title' => 'title']
        );
        $pickupStoreCollection->addFieldToFilter('ma.component_mode', \Ess\M2ePro\Helper\Component\Ebay::NICK);

        // Set collection to grid
        $this->setCollection($pickupStoreCollection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('name', [
            'header'    => $this->__('Name'),
            'align'     => 'left',
            'type'      => 'text',
            'index'     => 'name',
            'filter_index' => 'main_table.name'
        ]);

        $this->addColumn('location_id', [
            'header'    => $this->__('Location ID'),
            'align'     => 'left',
            'type'      => 'text',
            'index'     => 'location_id',
            'escape'    => true,
            'filter_index' => 'main_table.location_id',
        ]);

        $this->addColumn('marketplace_id', [
            'header'    => $this->__('Country'),
            'align'     => 'left',
            'width'     => '200px',
            'type'      => 'options',
            'index'     => 'marketplace_id',
            'escape'    => true,
            'filter_index' => 'mm.title',
            'filter_condition_callback' => array($this, 'callbackFilterMarketplace'),
            'options' => $this->getEnabledMarketplaceTitles()
        ]);

        $this->addColumn('create_date', [
            'header'    => $this->__('Creation Date'),
            'align'     => 'left',
            'width'     => '150px',
            'type'      => 'datetime',
            'format' => \IntlDateFormatter::MEDIUM,
            'filter_time' => true,
            'index'     => 'create_date',
            'filter_index' => 'main_table.create_date'
        ]);

        $this->addColumn('update_date', [
            'header'    => $this->__('Update Date'),
            'align'     => 'left',
            'width'     => '150px',
            'type'      => 'datetime',
            'format' => \IntlDateFormatter::MEDIUM,
            'filter_time' => true,
            'index'     => 'update_date',
            'filter_index' => 'main_table.update_date'
        ]);

        $this->addColumn('actions', [
            'header'    => $this->__('Actions'),
            'align'     => 'left',
            'width'     => '100px',
            'type'      => 'action',
            'index'     => 'actions',
            'filter'    => false,
            'sortable'  => false,
            'getter'    => 'getId',
            'renderer'  => '\Ess\M2ePro\Block\Adminhtml\Magento\Grid\Column\Renderer\Action',
            'actions'   => [
                [
                    'caption'   => $this->__('Delete'),
                    'url'       => ['base' => '*/ebay_account_pickupStore/delete'],
                    'class'     => 'action-default scalable add primary account-delete-btn',
                    'field'     => 'id',
                    'confirm'  => $this->__('Are you sure?')
                ]
            ]
        ]);

        return parent::_prepareColumns();
    }

    //########################################

    protected function callbackFilterMarketplace($collection, $column)
    {
        $value = $column->getFilter()->getValue();

        if ($value == null) {
            return;
        }

        $collection->getSelect()->where('main_table.marketplace_id = ?', (int)$value);
    }

    //########################################

    public function getGridUrl()
    {
        return $this->getUrl('*/*/index', ['_current'=>true]);
    }

    public function getRowUrl($row)
    {
        return $this->getUrl('*/ebay_account_pickupStore/edit', ['id' => $row->getData('id')]);
    }

    //########################################

    private function getEnabledMarketplaceTitles()
    {
        $marketplaceCollection = $this->parentFactory->getObject(\Ess\M2ePro\Helper\Component\Ebay::NICK,'Marketplace')
                                      ->getCollection()
                                     ->addFieldToFilter('component_mode', \Ess\M2ePro\Helper\Component\Ebay::NICK)
                                     ->addFieldToFilter('status', \Ess\M2ePro\Model\Marketplace::STATUS_ENABLE)
                                     ->addFieldToFilter('is_in_store_pickup', 1)
                                     ->setOrder('sorder', 'ASC');

        $pickupStoreHelper = $this->getHelper('Component\Ebay\PickupStore');
        $options = array();
        foreach ($marketplaceCollection->getItems() as $marketplace) {
            $countryData = $pickupStoreHelper->convertMarketplaceToCountry(
                $marketplace->getChildObject()->getData()
            );
            $options[$marketplace->getData('id')] = $countryData['label'];
        }

        return $options;
    }

    //########################################
}
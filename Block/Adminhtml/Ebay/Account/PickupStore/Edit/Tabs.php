<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Account\PickupStore\Edit;

use Ess\M2ePro\Block\Adminhtml\Magento\Tabs\AbstractTabs;

class Tabs extends AbstractTabs
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('ebayAccountPickupStoreEditTabs');
        $this->setDestElementId('edit_form');
        // ---------------------------------------
    }

    //########################################

    protected function _prepareLayout()
    {
        $this->css->addFile('ebay/account/pickup_store.css');

        return parent::_prepareLayout();
    }

    //########################################

    protected function _beforeToHtml()
    {
        $this->addTab('general', array(
            'label'   => $this->__('General'),
            'title'   => $this->__('General'),
            'content' => $this->createBlock('Ebay\Account\PickupStore\Edit\Tabs\General')->toHtml(),
        ));

        $this->addTab('location', array(
            'label'   => $this->__('Location'),
            'title'   => $this->__('Location'),
            'content' => $this->createBlock('Ebay\Account\PickupStore\Edit\Tabs\Location')->toHtml(),
        ));

        $this->addTab('business_hours', array(
            'label'   => $this->__('Business Hours'),
            'title'   => $this->__('Business Hours'),
            'content' => $this->createBlock('Ebay\Account\PickupStore\Edit\Tabs\BusinessHours')->toHtml(),
        ));

        $this->addTab('stock_settings', array(
            'label'   => $this->__('Quantity Settings'),
            'title'   => $this->__('Quantity Settings'),
            'content' => $this->createBlock('Ebay\Account\PickupStore\Edit\Tabs\StockSettings')->toHtml(),
        ));

        $this->setActiveTab($this->getRequest()->getParam('tab', 'general'));

        return parent::_beforeToHtml();
    }

    //########################################
}
<?php

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Account\Edit;

use Ess\M2ePro\Block\Adminhtml\Magento\Tabs\AbstractTabs;

class Tabs extends AbstractTabs
{
    protected function _construct()
    {
        parent::_construct();

        $this->setId('amazonAccountEditTabs');
        $this->setDestElementId('edit_form');
    }

    protected function _beforeToHtml()
    {
        $this->addTab('general', array(
            'label'   => $this->__('General'),
            'title'   => $this->__('General'),
            'content' => $this->createBlock('Amazon\Account\Edit\Tabs\General')->toHtml(),
        ));

        $this->addTab('listingOther', array(
            'label'   => $this->__('3rd Party Listings'),
            'title'   => $this->__('3rd Party Listings'),
            'content' => $this->createBlock('Amazon\Account\Edit\Tabs\ListingOther')->toHtml(),
        ));

        $this->addTab('orders', array(
            'label'   => $this->__('Orders'),
            'title'   => $this->__('Orders'),
            'content' => $this->createBlock('Amazon\Account\Edit\Tabs\Order')->toHtml(),
        ));

        $this->addTab('shipping_settings', array(
            'label'   => $this->__('Shipping Settings'),
            'title'   => $this->__('Shipping Settings'),
            'content' => $this->createBlock('Amazon\Account\Edit\Tabs\ShippingSettings')->toHtml(),
        ));

        if ($this->getHelper('Component\Amazon\Repricing')->isEnabled() &&
            $this->getHelper('Data\GlobalData')->getValue('edit_account')) {

            $this->addTab('repricing', array(
                'label'   => $this->__('Repricing Tool'),
                'title'   => $this->__('Repricing Tool'),
                'content' => $this->createBlock('Amazon\Account\Edit\Tabs\Repricing')->toHtml(),
            ));
        }

        $this->setActiveTab($this->getRequest()->getParam('tab', 'general'));

        $this->js->addOnReadyJs(<<<JS

    var urlHash = location.hash.substr(1);
    if (urlHash != '') {
        setTimeout(function() {
            jQuery('#{$this->getId()}').tabs(
                'option', 'active', jQuery('li[aria-labelledby="{$this->getId()}_' + urlHash + '"]').index()
                );
            location.hash = '';
        }, 100);
    }
JS
        );

        return parent::_beforeToHtml();
    }
}
<?php

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Account\Edit;

use Ess\M2ePro\Block\Adminhtml\Magento\Tabs\AbstractTabs;

class Tabs extends AbstractTabs
{
    protected function _construct()
    {
        parent::_construct();

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

        // TODO NOT SUPPORTED FEATURES
//        if ($this->getHelper('Component\Amazon')->isRepricingEnabled() &&
//            $this->getHelper('Data\GlobalData')->getValue('temp_data')->getId()) {
//
//            $this->addTab('repricing', array(
//                'label'   => $this->__('Repricing Tool'),
//                'title'   => $this->__('Repricing Tool'),
//                'content' => $this->getLayout()
//                    ->createBlock('M2ePro/adminhtml_common_amazon_account_edit_tabs_repricing')->toHtml(),
//            ));
//        }

        $this->setActiveTab($this->getRequest()->getParam('tab', 'general'));

        return parent::_beforeToHtml();
    }
}
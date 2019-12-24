<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Account\Edit;

use Ess\M2ePro\Block\Adminhtml\Magento\Tabs\AbstractTabs;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Amazon\Account\Edit\Tabs
 */
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
        /** @var $account \Ess\M2ePro\Model\Account */
        $account = $this->getHelper('Data\GlobalData')->getValue('edit_account');

        $this->addTab('general', [
            'label'   => $this->__('General'),
            'title'   => $this->__('General'),
            'content' => $this->createBlock('Amazon_Account_Edit_Tabs_General')->toHtml(),
        ]);

        $this->addTab('listingOther', [
            'label'   => $this->__('3rd Party Listings'),
            'title'   => $this->__('3rd Party Listings'),
            'content' => $this->createBlock('Amazon_Account_Edit_Tabs_ListingOther')->toHtml(),
        ]);

        $this->addTab('orders', [
            'label'   => $this->__('Orders'),
            'title'   => $this->__('Orders'),
            'content' => $this->createBlock('Amazon_Account_Edit_Tabs_Order')->toHtml(),
        ]);

        $this->addTab('shipping_settings', [
            'label'   => $this->__('Shipping Settings'),
            'title'   => $this->__('Shipping Settings'),
            'content' => $this->createBlock('Amazon_Account_Edit_Tabs_ShippingSettings')->toHtml(),
        ]);

        if ($account !== null
            && $account->getId()
            && $account->getChildObject()->getMarketplace()->getChildObject()->isVatCalculationServiceAvailable()
        ) {
            $this->addTab('vat_calculation_service', [
                'label'   => $this->__('VAT Calculation Service'),
                'title'   => $this->__('VAT Calculation Service'),
                'content' => $this->createBlock('Amazon_Account_Edit_Tabs_VatCalculationService')->toHtml(),
            ]);
        }

        if ($account !== null
            && $account->getId()
            && $this->getHelper('Component_Amazon_Repricing')->isEnabled()
        ) {
            $this->addTab('repricing', [
                'label'   => $this->__('Repricing Tool'),
                'title'   => $this->__('Repricing Tool'),
                'content' => $this->createBlock('Amazon_Account_Edit_Tabs_Repricing')->toHtml(),
            ]);
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

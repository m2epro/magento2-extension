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

        if ($account !== null
            && $account->getId()
            && $account->getChildObject()->getMarketplace()->getChildObject()->isVatCalculationServiceAvailable()
        ) {
            $this->addTab('vcs_upload_invoices', [
                'label'   => $this->__('VCS / Upload Invoices'),
                'title'   => $this->__('VCS / Upload Invoices'),
                'content' => $this->createBlock('Amazon_Account_Edit_Tabs_VCSUploadInvoices')->toHtml(),
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

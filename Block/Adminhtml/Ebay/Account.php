<?php

namespace Ess\M2ePro\Block\Adminhtml\Ebay;

use Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractContainer;

class Account extends AbstractContainer
{
    protected function _construct()
    {
        parent::_construct();
        $this->_controller = 'adminhtml_ebay_account';

        $this->buttonList->update('add', 'label', $this->__('Add Account'));
        $this->buttonList->update('add', 'onclick', 'setLocation(\''.$this->getUrl('*/ebay_account/new').'\');');
    }

    protected function _prepareLayout()
    {
        $this->appendHelpBlock([
            'content' => $this->__(<<<HTML
<p>On this Page you can find information about eBay Accounts which can be managed via M2E Pro.</p><br>
<p>Settings for such configurations as eBay Orders along with Magento Order creation conditions,
3rd Party Listings import including options of Mapping them to Magento Products and Moving them to M2E Pro Listings,
etc. can be specified for each Account separately.</p><br>
<p><strong>Note:</strong> eBay Account can be deleted only if it is not being used for any of M2E Pro Listings.</p>
HTML
)
        ]);

        $this->jsUrl->addUrls($this->getHelper('Data')->getControllerActions('Ebay\Account\Feedback'));

        $this->jsTranslator->addTranslations([
            'Should be between 2 and 80 characters long.' => $this->__('Should be between 2 and 80 characters long.')
        ]);

        $this->css->addFile('ebay/account/feedback.css');

        return parent::_prepareLayout();
    }

    protected function _toHtml()
    {
        $this->js->add(<<<JS

    require([
        'M2ePro/Ebay/Account/Grid'
    ], function(){

        window.EbayAccountGridObj = new EbayAccountGrid(
            '{$this->getChildBlock('grid')->getId()}'
        );
    });
JS
        );

        return parent::_toHtml();
    }

}
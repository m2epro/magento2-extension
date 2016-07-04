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
<p><strong>Note:</strong> eBay Account can be deleted only if it is not being used for any of M2E Pro Listings.</p><br>
<p>More detailed information you can find <a href="%url%" target="_blank">here</a>.</p>
HTML
                ,
                $this->getHelper('Module\Support')->getDocumentationUrl(NULL, NULL, 'x/4gEtAQ')
)
        ]);

        return parent::_prepareLayout();
    }
}
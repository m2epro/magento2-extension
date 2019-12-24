<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Amazon\Account
 */
class Account extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractContainer
{
    //########################################

    protected function _construct()
    {
        parent::_construct();

        // Set buttons actions
        // ---------------------------------------
        $this->removeButton('back');
        $this->removeButton('reset');
        $this->removeButton('delete');
        $this->removeButton('save');
        $this->removeButton('edit');
        // ---------------------------------------

        $this->_controller = 'adminhtml_amazon_account';

        $url = $this->getUrl('*/amazon_account/new');
        $this->buttonList->update('add', 'label', $this->__('Add Account'));
        $this->buttonList->update('add', 'onclick', 'setLocation(\''.$url.'\');');
    }

    protected function _prepareLayout()
    {
        $this->appendHelpBlock([
            'content' => $this->__(<<<HTML
<p>On this Page, you can find information about Amazon Accounts which are managed via M2E Pro.
M2E Pro Amazon Account is a combination of Merchant ID and particular Marketplace.
For example, to sell Items on Amazon.es, Amazon.it, you need to add two separate M2E Pro Amazon Accounts for
Spain and Italy Marketplaces using your Merchant ID for Amazon Europe.</p><br>
<p><strong>Note:</strong> prior to adding a new M2E Pro Amazon Account, enable an appropriate Marketplace under the
Configuration > Marketplaces.</p>
<p>Each Account is configured separately. Specify your preferences for the 3rd Party Listing import,
Channel Order management, Magento Order creation rules, etc.</p><br>
<p><strong>Note:</strong> M2E Pro Amazon Account can be deleted only if it is not being used
for any of M2E Pro Listings.</p>
HTML
        )
        ]);

        return parent::_prepareLayout();
    }

    //########################################
}

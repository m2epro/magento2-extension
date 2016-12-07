<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon;

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
<p>On this Page you can find information about Amazon Accounts which can be managed via M2E Pro.
M2E Pro Amazon Account is a combination of Merchant ID and a particular Marketplace.
In order to sell Items on Amazon.es, Amazon.it etc., you need to add separate M2E Pro Accounts for
Spain and Italy Marketplaces, although you can still use the same Merchant ID. </p><br>
<p>Settings for such configurations as eBay Orders along with Magento Order creation conditions,
3rd Party Listings import including options of Mapping them to Magento Products and Moving them to M2E Pro Listings,
etc. can be specified for each Account separately.</p><br>
<p><strong>Note:</strong> Amazon Account can be deleted only if it is not being used
for any of M2E Pro Listings.</p>
HTML
)
        ]);

        return parent::_prepareLayout();
    }

    //########################################
}
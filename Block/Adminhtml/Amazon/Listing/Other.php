<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Listing;

class Other extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractContainer
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('amazonListingOther');
        $this->_controller = 'adminhtml_amazon_listing_other';
        // ---------------------------------------

        // Set buttons actions
        // ---------------------------------------
        $this->buttonList->remove('back');
        $this->buttonList->remove('reset');
        $this->buttonList->remove('delete');
        $this->buttonList->remove('add');
        $this->buttonList->remove('save');
        $this->buttonList->remove('edit');
        // ---------------------------------------

        $this->isAjax = $this->getHelper('Data')->jsonEncode($this->getRequest()->isXmlHttpRequest());
    }

    protected function _prepareLayout()
    {
        $this->appendHelpBlock([
            'content' => $this->__(
                <<<HTML
                <p>The list below displays groups of Items combined together based on their belonging to a
                specific Marketplace and Account. The number of the 3rd Party Listings available for each of
                the groups is also available.</p><br>

                <p>3rd Party Listings are the Items which were placed directly on the Channel or by using a tool
                other than M2E Pro. These Items are imported according to Account settings which means the settings
                can be managed for different Accounts separately.</p><br>

                <p>Information in this section can be used to see which Items have not been fully managed via M2E Pro
                yet. It allows mapping the imported Channel Products to the Magento Products and further moving
                them into M2E Pro Listings.</p>

HTML
            )
        ]);

        return parent::_prepareLayout();
    }

    //########################################
}
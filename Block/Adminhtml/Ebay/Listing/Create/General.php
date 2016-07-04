<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Create;

class General extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractContainer
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('ebayListingAccountMarketplace');
        $this->_controller = 'adminhtml_ebay_listing_create';
        $this->_mode = 'general';
        // ---------------------------------------

        // Set header text
        // ---------------------------------------
        $this->_headerText = $this->__('Creating A New M2E Pro Listing');
        // ---------------------------------------

        // Set buttons actions
        // ---------------------------------------
        $this->removeButton('back');
        $this->removeButton('reset');
        $this->removeButton('delete');
        $this->removeButton('add');
        $this->removeButton('save');
        $this->removeButton('edit');
        // ---------------------------------------

        // ---------------------------------------
        $this->addButton('next', array(
            'label'     => $this->__('Next Step'),
            'class'     => 'action-primary next_step_button forward'
        ));
        // ---------------------------------------
    }

    //########################################

    protected function _toHtml()
    {

        $breadcrumb = $this->createBlock('Ebay\Listing\Create\Breadcrumb');
        $breadcrumb->setSelectedStep(1);

        $helpBlock = $this->createBlock('HelpBlock');
        $helpBlock->addData([
            'content' => $this->__(
                '<p>It is necessary to select an eBay Account (existing or create a new one) as well as choose a
                Marketplace that you are going to sell Magento Products on.</p><br>
                <p>It is also important to specify a Store View in accordance with which Magento Attribute values
                will be used in the Listing settings.</p><br>
                <p>More detailed information you can find <a href="%url%" target="_blank">here</a>.</p>',
                $this->getHelper('Module\Support')->getDocumentationUrl(NULL, NULL, 'x/6wItAQ')
            ),
            'style' => 'margin-top: 30px'
        ]);

        return
            $breadcrumb->_toHtml() .
            '<div id="progress_bar"></div>' .
            $helpBlock->toHtml() .
            '<div id="content_container">' . parent::_toHtml() . '</div>';
    }

    //########################################
}
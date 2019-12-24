<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay;

use Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractContainer;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Ebay\Listing
 */
class Listing extends AbstractContainer
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('ebayListing');
        $this->_controller = 'adminhtml_ebay_listing';
        // ---------------------------------------
    }

    //########################################

    protected function _prepareLayout()
    {
        $content = $this->__(
            '<p>This page displays the list of M2E Pro Listings. Generally, a Listing is a group of Magento Products
            sold on a certain Marketplace by a particular Seller and managed by the same Selling, Synchronization, etc.
            Policy Settings.</p><br>
            <p>Each Magento Product can be placed only once in each M2E Pro Listing.</p><br>
            <p><strong>Note:</strong> Products which are not listed via M2E Pro will be automatically added to the
            3rd Party Listings if the import option is enabled in the Account settings.</p>'
        );

        $this->appendHelpBlock([
            'content' => $content
        ]);

        // ---------------------------------------
        $url = $this->getUrl('*/ebay_listing_create/index', ['step' => 1, 'clear' => 1]);
        $this->addButton('add', [
            'label'     => $this->__('Add Listing'),
            'onclick'   => 'setLocation(\'' .$url.'\')',
            'class' => 'action-primary',
            'button_class' => '',
        ]);
        // ---------------------------------------

        return parent::_prepareLayout();
    }

    //########################################
}

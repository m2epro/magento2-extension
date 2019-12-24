<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Amazon\Listing
 */
class Listing extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractContainer
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        $this->_controller = 'adminhtml_amazon_listing';

        // ---------------------------------------
        $url = $this->getUrl('*/amazon_listing_create/index', [
            'step' => '1',
            'clear' => 'yes'
        ]);
        $this->addButton('add', [
            'label'     => $this->__('Add Listing'),
            'onclick'   => 'setLocation(\'' . $url . '\')',
            'class'     => 'action-primary'
        ]);
        // ---------------------------------------
    }

    //########################################

    protected function _prepareLayout()
    {
        $content = $this->__(
            '<p>This page displays the list of M2E Pro Listings. Generally, a Listing is a group of Magento Products
            sold on a certain Marketplace by a particular Seller and managed by the same Selling, Synchronization,
            etc. Policy Settings.</p><br>

            <p><strong>Note:</strong> Products which are not listed via M2E Pro will be automatically added to the
            3rd Party Listings if the import option is enabled in the Account settings.</p>'
        );

        $this->appendHelpBlock([
            'content' => $content
        ]);

        return parent::_prepareLayout();
    }

    //########################################
}

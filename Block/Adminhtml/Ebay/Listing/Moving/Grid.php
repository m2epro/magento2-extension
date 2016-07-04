<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Moving;

class Grid extends \Ess\M2ePro\Block\Adminhtml\Listing\Moving\Grid
{
    //########################################

    protected function getNewListingBtnHtml()
    {
        // ---------------------------------------
        $newListingUrl = $this->getUrl('*/ebay_listing_create/index', array(
            'step' => 1,
            'clear' => 1,
            'account_id' => $this->getHelper('Data\GlobalData')->getValue('accountId'),
            'marketplace_id' => $this->getHelper('Data\GlobalData')->getValue('marketplaceId'),
            'creation_mode' => \Ess\M2ePro\Helper\View::LISTING_CREATION_MODE_LISTING_ONLY
        ));

        $data = array(
            'id'    => 'listingProductMoving_addNew_listing_button',
            'label' => $this->__('Add New Listing'),
            'style' => 'float: right;',
            'class' => 'primary',
            'onclick' => $this->getData('moving_handler_js') . '.startListingCreation(\''.$newListingUrl.'\')'
        );
        $buttonBlock = $this->createBlock('Magento\Button')->setData($data);
        // ---------------------------------------

        return $buttonBlock->toHtml();
    }

    //########################################
}
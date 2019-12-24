<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\PickupStore\Variation\Product;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\PickupStore\Variation\Product\View
 */
class View extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractContainer
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        $this->setId('ebayListingPickupStoreVariationProductView');
        $this->_controller = 'adminhtml_ebay_listing_pickupStore_variation_product_view';

        $this->removeButton('add');
    }

    //########################################

    protected function _toHtml()
    {
        $helpBlock = $this->createBlock('HelpBlock', '', [
            'data' => [
                'content' => $this->__('
                In this section, you can observe Store and Product Variation details as well as their Quantity and Logs
                for each Variation separately.
            ')
            ]
        ]);

        return $helpBlock->toHtml() . parent::_toHtml();
    }

    //########################################
}

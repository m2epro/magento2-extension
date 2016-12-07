<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\PickupStore\Step\Products;

class Wrapper extends \Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('ebayListingPickupStoreProductsWrapper');
        // ---------------------------------------
    }

    protected function _toHtml()
    {
        $helpBlock = $this->createBlock('HelpBlock', '', [
            'data' => [
                'content' => $this->__('
                In this section, you can <strong>review</strong> Store and Product details as well as Product Quantity
                and Logs.<br/>
                Press <strong>Assign Products to Stores</strong> button to add new Products to the selected Store for
                In-Store Pickup Service.<br/>
                If you want to <strong>unassign</strong> the Product from the Store you can use a
                <strong>Unassign Option</strong> from the Actions bulk at the top of the Grid.
            ')
            ]
        ]);

        $breadcrumb = $this->createBlock('Ebay\Listing\PickupStore\Breadcrumb');
        $breadcrumb->setSelectedStep(1);

        $grid = $this->createBlock('Ebay\Listing\PickupStore\Step\Products\Grid');

        return $helpBlock->toHtml() . $breadcrumb->toHtml() . parent::_toHtml() . $grid->toHtml();
    }

    //########################################
}
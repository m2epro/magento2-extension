<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Variation\Product\Manage;

use Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractContainer;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Variation\Product\Manage\View
 */
class View extends AbstractContainer
{
    protected function _construct()
    {
        parent::_construct();

        $this->_controller = 'adminhtml_ebay_listing_variation_product_manage_view';

        $this->removeButton('add');
    }

    protected function _toHtml()
    {
        $block = $this->createBlock('HelpBlock')->setData([
            'content' => $this->__(
                'In this Section you can find all Item Variations with possibility to search, filter,
                sort etc.<br/><br/>

                You can add eBay Catalog Identifiers for each Variation separately.
                In case the Value of eBay Catalog Identifier
                is provided here it will be used in priority over eBay Catalog Identifiers
                Settings of Description Policy.<br/><br/>

                For Bundle Products and Simple Products with Custom Options Settings for
                eBay Catalog Identifiers can be
                provided only for each Variation separately here, as Description Policy Settings cannot be applied
                for them.<br/><br/>

                <strong>Note:</strong> markers <strong>"will be added"</strong> and <strong>"will be deleted"</strong>
                mean that Variation will be Added/Removed during the next Revise Action.'
            )
        ]);

        return $block->toHtml() . parent::_toHtml();
    }
}

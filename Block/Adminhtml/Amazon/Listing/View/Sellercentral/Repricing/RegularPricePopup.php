<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  2011-2017 ESS-UA [M2E Pro]
 * @license    Any usage is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Listing\View\Sellercentral\Repricing;

class RegularPricePopup extends \Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock
{
    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('amazonRepricingRegularPricePopup');
        // ---------------------------------------

        $this->setTemplate('amazon/listing/view/sellercentral/repricing/regular_price_popup.phtml');
    }
}
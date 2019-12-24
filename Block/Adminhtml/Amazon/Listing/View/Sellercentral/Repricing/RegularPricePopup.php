<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Listing\View\Sellercentral\Repricing;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Amazon\Listing\View\Sellercentral\Repricing\RegularPricePopup
 */
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

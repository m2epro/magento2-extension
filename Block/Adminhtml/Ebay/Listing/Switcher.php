<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing;

use \Ess\M2ePro\Block\Adminhtml\Listing\Switcher as AbstractSwitcher;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Switcher
 */
class Switcher extends AbstractSwitcher
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block

        $this->setAddListingUrl('*/ebay_listing_create/index');
    }

    //########################################
}

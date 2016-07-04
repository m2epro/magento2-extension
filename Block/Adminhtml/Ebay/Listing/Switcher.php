<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing;

use \Ess\M2ePro\Block\Adminhtml\Listing\Switcher as AbstractSwitcher;

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
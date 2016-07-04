<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Product\Add\NewAsin\Manual;

class SkipPopup extends \Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('amazonListingAddNewAsinManualPopup');
        // ---------------------------------------

        $this->setTemplate('amazon/listing/product/add/new_asin/manual/skip_popup.phtml');
    }

    //########################################
}
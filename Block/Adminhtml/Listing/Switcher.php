<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Listing;

use \Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock;

abstract class Switcher extends AbstractBlock
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        $this->setAddListingUrl('');

        $this->setTemplate('Ess_M2ePro::listing/switcher.phtml');
    }

    //########################################
}
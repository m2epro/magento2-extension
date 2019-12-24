<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Listing;

use \Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Listing\Switcher
 */
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

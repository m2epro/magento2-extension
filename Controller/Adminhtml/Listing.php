<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml;

abstract class Listing extends \Ess\M2ePro\Controller\Adminhtml\Base
{
    //########################################

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Ess_M2ePro::ebay_listings') ||
               $this->_authorization->isAllowed('Ess_M2ePro::amazon_listings');
    }

    //########################################
}
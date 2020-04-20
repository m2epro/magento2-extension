<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Listing
 */
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

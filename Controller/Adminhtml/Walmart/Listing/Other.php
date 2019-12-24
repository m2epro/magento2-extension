<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Listing;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Other
 */
abstract class Other extends \Ess\M2ePro\Controller\Adminhtml\Walmart\Listing
{
    //########################################

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Ess_M2ePro::walmart_listings_other');
    }

    //########################################
}

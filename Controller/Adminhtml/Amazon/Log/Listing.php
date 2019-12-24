<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Log;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Amazon\Log\Listing
 */
abstract class Listing extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing
{
    //########################################

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Ess_M2ePro::amazon_listings_logs');
    }

    //########################################
}

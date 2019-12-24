<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Walmart\Listing
 */
abstract class Listing extends \Ess\M2ePro\Controller\Adminhtml\Walmart\Main
{
    //########################################

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Ess_M2ePro::walmart_listings');
    }

    //########################################
}

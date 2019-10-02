<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Log;

/**
 * Class Listing
 * @package Ess\M2ePro\Controller\Adminhtml\Ebay\Log
 */
abstract class Listing extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Main
{
    //########################################

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Ess_M2ePro::ebay_listings_logs');
    }

    //########################################
}

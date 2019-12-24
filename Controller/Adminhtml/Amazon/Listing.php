<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing
 */
abstract class Listing extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Main
{
    //########################################

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Ess_M2ePro::amazon_listings');
    }

    //########################################
}

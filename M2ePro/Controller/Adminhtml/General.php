<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\General
 */
abstract class General extends \Ess\M2ePro\Controller\Adminhtml\Base
{
    //########################################

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Ess_M2ePro::ebay') ||
               $this->_authorization->isAllowed('Ess_M2ePro::amazon');
    }

    //########################################
}

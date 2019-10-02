<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Template\Description;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Template\Description;

/**
 * Class NewAction
 * @package Ess\M2ePro\Controller\Adminhtml\Amazon\Template\Description
 */
class NewAction extends Description
{
    //########################################

    public function execute()
    {
        $this->_forward('edit');
    }

    //########################################
}

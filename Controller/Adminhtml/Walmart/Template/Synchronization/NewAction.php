<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Template\Synchronization;

use Ess\M2ePro\Controller\Adminhtml\Walmart\Template;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Walmart\Template\Synchronization\NewAction
 */
class NewAction extends Template
{
    public function execute()
    {
        $this->_forward('edit');
    }
}

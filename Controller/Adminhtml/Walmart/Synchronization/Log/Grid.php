<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Synchronization\Log;

class Grid extends \Ess\M2ePro\Controller\Adminhtml\Walmart\Settings
{
    //########################################

    public function execute()
    {
        $this->setAjaxContent($this->createBlock('Walmart\Synchronization\Log\Grid'));

        return $this->getResult();
    }

    //########################################
}
<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Synchronization\Log;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Amazon\Synchronization\Log\Grid
 */
class Grid extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Settings
{
    //########################################

    public function execute()
    {
        $this->setAjaxContent($this->createBlock('Amazon_Synchronization_Log_Grid'));

        return $this->getResult();
    }

    //########################################
}

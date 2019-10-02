<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Developers;

use Ess\M2ePro\Controller\Adminhtml\Developers;

/**
 * Class SynchronizationLogGrid
 * @package Ess\M2ePro\Controller\Adminhtml\Developers
 */
class SynchronizationLogGrid extends Developers
{
    //########################################

    public function execute()
    {
        $this->setAjaxContent($this->createBlock('Developers_Tabs_SynchronizationLog_Grid'));
        return $this->getResult();
    }

    //########################################
}

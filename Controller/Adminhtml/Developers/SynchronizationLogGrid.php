<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Developers;

use Ess\M2ePro\Controller\Adminhtml\Developers;

class SynchronizationLogGrid extends Developers
{
    //########################################

    public function execute()
    {
        $this->setAjaxContent($this->createBlock('Developers\Tabs\SynchronizationLog\Grid'));
        return $this->getResult();
    }

    //########################################
}
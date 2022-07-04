<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Developers;

use Ess\M2ePro\Controller\Adminhtml\Developers;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Developers\SynchronizationLogGrid
 */
class SynchronizationLogGrid extends Developers
{
    //########################################

    public function execute()
    {
        $this->setAjaxContent(
            $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Developers\Tabs\SynchronizationLog\Grid::class)
        );
        return $this->getResult();
    }

    //########################################
}

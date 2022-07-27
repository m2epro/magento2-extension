<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Synchronization\Log;

class Grid extends \Ess\M2ePro\Controller\Adminhtml\Walmart\Settings
{
    public function execute()
    {
        $this->setAjaxContent(
            $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Walmart\Synchronization\Log\Grid::class)
        );

        return $this->getResult();
    }
}

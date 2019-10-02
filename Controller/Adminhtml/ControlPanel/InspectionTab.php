<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\ControlPanel;

use Ess\M2ePro\Helper\Module;
use Magento\Backend\App\Action;

/**
 * Class InspectionTab
 * @package Ess\M2ePro\Controller\Adminhtml\ControlPanel
 */
class InspectionTab extends Main
{
    public function execute()
    {
        $block = $this->createBlock('ControlPanel_Tabs_Inspection', '');
        $this->setAjaxContent($block);

        return $this->getResult();
    }
}

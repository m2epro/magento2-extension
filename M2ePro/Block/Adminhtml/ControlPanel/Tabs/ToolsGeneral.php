<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\ControlPanel\Tabs;

use Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock;
use Ess\M2ePro\Helper\View\ControlPanel\Command;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\ControlPanel\Tabs\ToolsGeneral
 */
class ToolsGeneral extends AbstractBlock
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('controlPanelToolsGeneral');
        // ---------------------------------------

        $this->setTemplate('control_panel/tabs/tools_general.phtml');
    }

    //########################################

    protected function _beforeToHtml()
    {
        $this->setChild('tabs', $this->createBlock('ControlPanel_Tabs_ToolsGeneral_Tabs'));
        return parent::_beforeToHtml();
    }

    //########################################
}

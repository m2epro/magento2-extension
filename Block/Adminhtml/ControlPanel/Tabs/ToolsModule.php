<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\ControlPanel\Tabs;

use Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\ControlPanel\Tabs\ToolsModule
 */
class ToolsModule extends AbstractBlock
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('controlPanelToolsModule');
        // ---------------------------------------

        $this->setTemplate('control_panel/tabs/tools_module.phtml');
    }

    //########################################

    protected function _beforeToHtml()
    {
        $this->setChild('tabs', $this->createBlock('ControlPanel_Tabs_ToolsModule_Tabs'));
        return parent::_beforeToHtml();
    }

    //########################################
}

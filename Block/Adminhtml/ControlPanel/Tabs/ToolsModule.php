<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\ControlPanel\Tabs;

use Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock;

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
        $this->setChild('tabs', $this->createBlock('ControlPanel\Tabs\ToolsModule\Tabs'));
        return parent::_beforeToHtml();
    }

    //########################################
}
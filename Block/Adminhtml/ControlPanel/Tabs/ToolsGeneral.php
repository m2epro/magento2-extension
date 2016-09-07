<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\ControlPanel\Tabs;

use Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock;
use Ess\M2ePro\Helper\View\ControlPanel\Command;

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
        $this->setChild('tabs', $this->createBlock('ControlPanel\Tabs\ToolsGeneral\Tabs'));
        return parent::_beforeToHtml();
    }

    //########################################
}
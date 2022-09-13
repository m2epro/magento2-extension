<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\ControlPanel\Tabs;

use Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\ControlPanel\Tabs\ToolsModule
 */
class ChangeTracker extends AbstractBlock
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('controlPanelChangeTracker');
        // ---------------------------------------

        $this->setTemplate('control_panel/tabs/change_tracker/index.phtml');
    }

    //########################################

    protected function _beforeToHtml()
    {
        $this->setChild('tabs',
            $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\ControlPanel\Tabs\ChangeTracker\Tabs::class)
        );
        return parent::_beforeToHtml();
    }

    //########################################
}

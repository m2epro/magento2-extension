<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\ControlPanel\Info;

use Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\ControlPanel\Info\Inspection
 */
class Inspection extends AbstractBlock
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('controlPanelSummaryInspection');
        // ---------------------------------------

        $this->setTemplate('control_panel/info/inspection.phtml');
    }

    //########################################

    protected function _beforeToHtml()
    {
        $this->mainChecks = sprintf(
            '<a href="%s" target="_blank">%s</a>',
            $this->getUrl('*/controlPanel_inspection/mainChecks'),
            $this->__('Show')
        );
        // ---------------------------------------

        return parent::_beforeToHtml();
    }

    //########################################
}

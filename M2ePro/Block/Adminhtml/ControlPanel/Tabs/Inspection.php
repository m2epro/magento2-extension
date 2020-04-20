<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\ControlPanel\Tabs;

use Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\ControlPanel\Tabs\Inspection
 */
class Inspection extends AbstractBlock
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('controlPanelInspection');
        // ---------------------------------------

        $this->setTemplate('control_panel/tabs/inspection.phtml');
    }

    //########################################

    protected function _beforeToHtml()
    {
        // ---------------------------------------
        $this->setChild('requirements', $this->createBlock(
            'ControlPanel_Inspection_Requirements'
        ));
        $this->setChild('cron', $this->createBlock(
            'ControlPanel_Inspection_Cron'
        ));
        // ---------------------------------------

        // ---------------------------------------
        $this->setChild('caches', $this->createBlock(
            'ControlPanel_Inspection_Caches'
        ));
        $this->setChild('conflicted_modules', $this->createBlock(
            'ControlPanel_Inspection_ConflictedModules'
        ));
        $this->setChild('database_broken', $this->createBlock(
            'ControlPanel_Inspection_DatabaseBrokenTables'
        ));
        $this->setChild('other_issues', $this->createBlock(
            'ControlPanel_Inspection_OtherIssues'
        ));
        // ---------------------------------------

        return parent::_beforeToHtml();
    }

    //########################################
}

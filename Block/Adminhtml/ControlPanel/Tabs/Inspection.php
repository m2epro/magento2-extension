<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\ControlPanel\Tabs;

use Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock;

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
        $this->setChild('requirements', $this->getLayout()->createBlock(
            '\Ess\M2ePro\Block\Adminhtml\ControlPanel\Inspection\Requirements'
        ));
        $this->setChild('cron', $this->getLayout()->createBlock(
            '\Ess\M2ePro\Block\Adminhtml\ControlPanel\Inspection\Cron'
        ));
        // ---------------------------------------

        // ---------------------------------------
        $this->setChild('caches', $this->getLayout()->createBlock(
            '\Ess\M2ePro\Block\Adminhtml\ControlPanel\Inspection\Caches'
        ));
        $this->setChild('conflicted_modules', $this->getLayout()->createBlock(
            '\Ess\M2ePro\Block\Adminhtml\ControlPanel\Inspection\ConflictedModules'
        ));
        $this->setChild('database_broken', $this->getLayout()->createBlock(
            '\Ess\M2ePro\Block\Adminhtml\ControlPanel\Inspection\DatabaseBrokenTables'
        ));
        $this->setChild('other_issues', $this->getLayout()->createBlock(
            '\Ess\M2ePro\Block\Adminhtml\ControlPanel\Inspection\OtherIssues'
        ));
        // ---------------------------------------

        return parent::_beforeToHtml();
    }

    //########################################
}
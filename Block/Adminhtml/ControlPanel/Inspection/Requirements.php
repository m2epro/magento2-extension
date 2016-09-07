<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\ControlPanel\Inspection;

class Requirements extends AbstractInspection
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('controlPanelInspectionRequirements');
        // ---------------------------------------

        $this->setTemplate('control_panel/inspection/requirements.phtml');
    }

    //########################################

    protected function _beforeToHtml()
    {
        $this->requirements = $this->getHelper('Module')->getRequirementsInfo();

        return parent::_beforeToHtml();
    }

    //########################################
}
<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\ControlPanel\Inspection;

class ConflictedModules extends AbstractInspection
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('controlPanelInspectionConflictedModules');
        // ---------------------------------------

        $this->setTemplate('control_panel/inspection/conflictedModules.phtml');
    }

    //########################################

    public function isShown()
    {
        return count($this->getHelper('Magento')->getConflictedModules()) > 0;
    }

    //########################################
}
<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\ControlPanel\Inspection;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\ControlPanel\Inspection\ConflictedModules
 */
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
        return !empty($this->getHelper('Magento')->getConflictedModules());
    }

    //########################################
}

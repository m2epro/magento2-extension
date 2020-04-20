<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\ControlPanel\Inspection;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\ControlPanel\Inspection\Caches
 */
class Caches extends AbstractInspection
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('controlPanelInspectionCaches');
        // ---------------------------------------

        $this->setTemplate('control_panel/inspection/caches.phtml');
    }

    //########################################

    public function isShown()
    {
        return $this->getHelper('Client\Cache')->isApcAvailable();
    }

    //########################################
}

<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\ControlPanel\Inspection;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\ControlPanel\Inspection\Requirements
 */
class Requirements extends AbstractInspection
{
    protected $_template = 'control_panel/inspection/requirements.phtml';

    //########################################

    public function getManager()
    {
        return $this->modelFactory->getObject('Requirements\Manager');
    }

    //########################################
}

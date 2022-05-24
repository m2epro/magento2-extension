<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\ControlPanel\Inspection;

use Ess\M2ePro\Controller\Adminhtml\ControlPanel\Main;

class ChangeMaintenanceMode extends Main
{
    /** @var \Ess\M2ePro\Helper\View\ControlPanel */
    protected $controlPanelHelper;

    public function __construct(
        \Ess\M2ePro\Helper\View\ControlPanel $controlPanelHelper,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($context);
        $this->controlPanelHelper = $controlPanelHelper;
    }

    public function execute()
    {
        if ($this->getHelper('Module_Maintenance')->isEnabled()) {
            $this->getHelper('Module_Maintenance')->disable();
        } else {
            $this->getHelper('Module_Maintenance')->enable();
        }

        $this->messageManager->addSuccess($this->__('Changed.'));
        return $this->_redirect($this->controlPanelHelper->getPageUrl());
    }
}

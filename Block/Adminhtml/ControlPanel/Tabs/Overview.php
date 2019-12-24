<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\ControlPanel\Tabs;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm;
use Ess\M2ePro\Helper\Client;
use Ess\M2ePro\Helper\Magento;
use Ess\M2ePro\Helper\Module as ModuleHelper;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\ControlPanel\Tabs\Overview
 */
class Overview extends AbstractForm
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('controlPanelOverview');
        // ---------------------------------------

        $this->setTemplate('control_panel/tabs/overview.phtml');
    }

    //########################################

    protected function _beforeToHtml()
    {
        $this->setChild('actual_info', $this->createBlock(
            'ControlPanel_Info_Actual'
        ));

        $this->setChild('location_info', $this->createBlock(
            'ControlPanel_Info_Location'
        ));

        $this->setChild('database_module', $this->createBlock(
            'ControlPanel_Info_Mysql_Module'
        ));

        $this->setChild('database_integration', $this->createBlock(
            'ControlPanel_Info_Mysql_Integration'
        ));

        return parent::_beforeToHtml();
    }

    //########################################
}

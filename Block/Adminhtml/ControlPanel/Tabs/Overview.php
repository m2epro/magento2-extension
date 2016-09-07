<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\ControlPanel\Tabs;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm;
use Ess\M2ePro\Helper\Client;
use Ess\M2ePro\Helper\Magento;
use Ess\M2ePro\Helper\Module as ModuleHelper;

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
        $this->setChild('actual_info', $this->getLayout()->createBlock(
            '\Ess\M2ePro\Block\Adminhtml\ControlPanel\Info\Actual'
        ));

        $this->setChild('location_info', $this->getLayout()->createBlock(
            '\Ess\M2ePro\Block\Adminhtml\ControlPanel\Info\Location'
        ));

        $this->setChild('database_module', $this->getLayout()->createBlock(
            '\Ess\M2ePro\Block\Adminhtml\ControlPanel\Info\Mysql\Module'
        ));

        $this->setChild('database_integration', $this->getLayout()->createBlock(
            '\Ess\M2ePro\Block\Adminhtml\ControlPanel\Info\Mysql\Integration'
        ));

        return parent::_beforeToHtml();
    }

    //########################################
}
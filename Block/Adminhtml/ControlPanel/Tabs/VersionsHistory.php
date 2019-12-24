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
 * Class \Ess\M2ePro\Block\Adminhtml\ControlPanel\Tabs\VersionsHistory
 */
class VersionsHistory extends AbstractForm
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('controlPanelVersionsHistory');
        // ---------------------------------------

        $this->setTemplate('control_panel/tabs/versions_history.phtml');
    }

    //########################################

    protected function _beforeToHtml()
    {
        $this->setChild('inspection', $this->createBlock(
            'ControlPanel_Inspection_Installation'
        ));

        $this->setChild('setup_info', $this->createBlock(
            'ControlPanel_Info_Installation'
        ));

        $this->setChild('public_versions_history', $this->createBlock(
            'ControlPanel_Info_PublicVersionsHistory'
        ));

        return parent::_beforeToHtml();
    }

    //########################################
}

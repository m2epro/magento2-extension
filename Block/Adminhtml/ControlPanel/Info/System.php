<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\ControlPanel\Info;

use Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\ControlPanel\Info\System
 */
class System extends AbstractBlock
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('controlPanelAboutSystem');
        // ---------------------------------------

        $this->setTemplate('control_panel/info/system.phtml');
    }

    //########################################

    protected function _beforeToHtml()
    {
        $this->systemName = $this->getHelper('Client')->getSystem();
        $this->systemTime = $this->getHelper('Data')->getCurrentGmtDate();

        return parent::_beforeToHtml();
    }

    //########################################
}

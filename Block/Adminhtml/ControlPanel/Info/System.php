<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\ControlPanel\Info;

use Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock;

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
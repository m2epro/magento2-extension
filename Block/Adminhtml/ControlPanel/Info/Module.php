<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\ControlPanel\Info;

use Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock;

class Module extends AbstractBlock
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('controlPanelAboutModule');
        // ---------------------------------------

        $this->setTemplate('control_panel/info/module.phtml');
    }

    //########################################

    protected function _beforeToHtml()
    {
        $this->moduleName = $this->getHelper('Module')->getName();
        $this->moduleVersion = $this->getHelper('Module')->getPublicVersion();

        return parent::_beforeToHtml();
    }

    //########################################
}
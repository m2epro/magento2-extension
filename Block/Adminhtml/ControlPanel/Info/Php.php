<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\ControlPanel\Info;

use Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\ControlPanel\Info\Php
 */
class Php extends AbstractBlock
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('controlPanelAboutPhp');
        // ---------------------------------------

        $this->setTemplate('control_panel/info/php.phtml');
    }

    //########################################

    protected function _beforeToHtml()
    {
        $this->phpVersion = $this->getHelper('Client')->getPhpVersion();
        $this->phpApi = $this->getHelper('Client')->getPhpApiName();
        $this->phpSettings = $this->getHelper('Client')->getPhpSettings();

        return parent::_beforeToHtml();
    }

    //########################################
}

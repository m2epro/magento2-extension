<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\ControlPanel\Info;

use Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\ControlPanel\Info\Location
 */
class Location extends AbstractBlock
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('controlPanelAboutLocation');
        // ---------------------------------------

        $this->setTemplate('control_panel/info/location.phtml');
    }

    //########################################

    protected function _beforeToHtml()
    {
        $this->locationHost = $this->getHelper('Client')->getHost();
        $this->locationDomain = $this->getHelper('Client')->getDomain();
        $this->locationIp = $this->getHelper('Client')->getIp();
        $this->locationDirectory = $this->getHelper('Client')->getBaseDirectory();

        return parent::_beforeToHtml();
    }

    //########################################
}

<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\ControlPanel\Info;

use Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\ControlPanel\Info\Magento
 */
class Magento extends AbstractBlock
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('controlPanelAboutMagento');
        // ---------------------------------------

        $this->setTemplate('control_panel/info/magento.phtml');
    }

    //########################################

    protected function _beforeToHtml()
    {
        $this->platformMode = $this->__(ucwords($this->getHelper('Magento')->getEditionName()));
        $this->platformVersion = $this->getHelper('Magento')->getVersion();
        $this->platformIsSecretKey = $this->getHelper('Magento')->isSecretKeyToUrl();

        return parent::_beforeToHtml();
    }

    //########################################
}

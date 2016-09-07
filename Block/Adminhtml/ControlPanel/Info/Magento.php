<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\ControlPanel\Info;

use Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock;

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
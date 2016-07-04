<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay;

class GlobalSettings extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Main
{
    //########################################

    public function execute()
    {
        $block = $this->getLayout()->createBlock('Ess\M2ePro\Block\Adminhtml\System\Config\Tabs');

        $this->addContent($block);

        $this->resultPage->getConfig()->getTitle()->prepend($this->__('Global Settings'));

        return $this->resultPage;
    }

    //########################################
}
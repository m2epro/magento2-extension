<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\GlobalSettings
 */
class GlobalSettings extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Main
{
    //########################################

    public function execute()
    {
        $block = $this->createBlock('System_Config_Tabs');

        $this->addContent($block);

        $this->resultPage->getConfig()->getTitle()->prepend($this->__('Global Settings'));

        return $this->resultPage;
    }

    //########################################
}

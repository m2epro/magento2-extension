<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Amazon\GlobalSettings
 */
class GlobalSettings extends Main
{
    //########################################

    public function execute()
    {
        $this->addContent($this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\System\Config\Tabs::class));
        $this->getResult()->getConfig()->getTitle()->prepend($this->__('Global Settings'));

        return $this->getResult();
    }

    //########################################
}

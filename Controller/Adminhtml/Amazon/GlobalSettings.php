<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon;

class GlobalSettings extends Main
{
    //########################################

    public function execute()
    {
        $this->addContent($this->createBlock('System\Config\Tabs'));
        $this->getResult()->getConfig()->getTitle()->prepend($this->__('Global Settings'));

        return $this->getResult();
    }

    //########################################
}
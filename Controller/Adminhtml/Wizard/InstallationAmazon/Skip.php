<?php

namespace Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationAmazon;

use \Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationAmazon;
use \Ess\M2ePro\Helper\Module\Wizard;

class Skip extends InstallationAmazon
{
    public function execute()
    {
        $this->getHelper('Magento')->clearMenuCache();

        $this->setStatus(Wizard::STATUS_SKIPPED);

        $this->_redirect("*/amazon_listing/index/");
    }
}

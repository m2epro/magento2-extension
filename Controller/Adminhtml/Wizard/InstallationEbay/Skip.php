<?php

namespace Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationEbay;

use Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationEbay;
use Ess\M2ePro\Helper\Module\Wizard;

class Skip extends InstallationEbay
{
    public function execute()
    {
        $this->getHelper('Magento')->clearMenuCache();

        $this->setStatus(Wizard::STATUS_SKIPPED);

        $this->_redirect("*/ebay_listing/index/");
    }
}

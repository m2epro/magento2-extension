<?php

namespace Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationWalmart;

use Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationWalmart;
use Ess\M2ePro\Helper\Module\Wizard;

class Skip extends InstallationWalmart
{
    public function execute()
    {
        $this->getHelper('Magento')->clearMenuCache();

        $this->setStatus(Wizard::STATUS_SKIPPED);

        $this->_redirect("*/walmart_listing/index/");
    }
}

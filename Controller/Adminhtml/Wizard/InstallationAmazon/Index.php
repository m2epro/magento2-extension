<?php

namespace Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationAmazon;

use Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationAmazon;

class Index extends InstallationAmazon
{
    public function execute()
    {
        return $this->indexAction();
    }
}
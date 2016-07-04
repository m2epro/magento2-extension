<?php

namespace Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationAmazon;

use Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationAmazon;

class Registration extends InstallationAmazon
{
    public function execute()
    {
        $this->init();

        return $this->registrationAction();
    }
}
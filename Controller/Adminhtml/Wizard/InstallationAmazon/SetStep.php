<?php

namespace Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationAmazon;

use Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationAmazon;

class SetStep extends InstallationAmazon
{
    public function execute()
    {
        return $this->setStepAction();
    }
}
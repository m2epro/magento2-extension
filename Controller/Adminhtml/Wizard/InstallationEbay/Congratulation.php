<?php

namespace Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationEbay;

use Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationEbay;

class Congratulation extends InstallationEbay
{
    public function execute()
    {
        $this->init();

        return $this->congratulationAction();
    }
}
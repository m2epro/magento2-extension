<?php

namespace Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationAmazon;

use Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationAmazon;

class ListingTutorial extends InstallationAmazon
{
    public function execute()
    {
        $this->init();

        return $this->renderSimpleStep();
    }
}
<?php

namespace Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationEbay;

use Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationEbay;

class ListingTutorial extends InstallationEbay
{
     public function execute()
     {
         $this->init();

         return $this->renderSimpleStep();
     }
}
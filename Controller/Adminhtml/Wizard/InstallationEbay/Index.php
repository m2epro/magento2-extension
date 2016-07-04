<?php

namespace Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationEbay;

use Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationEbay;

class Index extends InstallationEbay
{
     public function execute()
     {
         return $this->indexAction();
     }
}
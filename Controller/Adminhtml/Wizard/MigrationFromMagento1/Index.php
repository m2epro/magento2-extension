<?php

namespace Ess\M2ePro\Controller\Adminhtml\Wizard\MigrationFromMagento1;

use Ess\M2ePro\Controller\Adminhtml\Wizard\MigrationFromMagento1;

class Index extends MigrationFromMagento1
{
    public function execute()
    {
        return $this->indexAction();
    }
}
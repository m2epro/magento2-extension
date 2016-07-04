<?php

namespace Ess\M2ePro\Controller\Adminhtml\Wizard\MigrationFromMagento1;

use Ess\M2ePro\Controller\Adminhtml\Wizard\MigrationFromMagento1;

class SetStep extends MigrationFromMagento1
{
    public function execute()
    {
        return $this->setStepAction();
    }
}
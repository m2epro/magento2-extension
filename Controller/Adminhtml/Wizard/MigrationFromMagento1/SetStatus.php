<?php

namespace Ess\M2ePro\Controller\Adminhtml\Wizard\MigrationFromMagento1;

use Ess\M2ePro\Controller\Adminhtml\Wizard\MigrationFromMagento1;

class SetStatus extends MigrationFromMagento1
{
    public function execute()
    {
        return $this->setStatusAction();
    }
}
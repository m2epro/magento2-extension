<?php

namespace Ess\M2ePro\Controller\Adminhtml\Wizard\MigrationFromMagento1;

use Ess\M2ePro\Controller\Adminhtml\Wizard\MigrationFromMagento1;

class Welcome extends MigrationFromMagento1
{
    public function execute()
    {
        $this->init();

        return $this->renderSimpleStep();
    }
}
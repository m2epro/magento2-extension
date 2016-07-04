<?php

namespace Ess\M2ePro\Controller\Adminhtml\Wizard\MigrationFromMagento1;

use Ess\M2ePro\Controller\Adminhtml\Wizard\MigrationFromMagento1;

class Congratulation extends MigrationFromMagento1
{
    public function execute()
    {
        $this->init();

        if ($this->isFinished()) {
            return $this->congratulationAction();
        }

        return $this->renderSimpleStep();
    }
}
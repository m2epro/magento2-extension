<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Wizard\MigrationFromMagento1;

use Ess\M2ePro\Controller\Adminhtml\Wizard\MigrationFromMagento1;

/**
 * Class Congratulation
 * @package Ess\M2ePro\Controller\Adminhtml\Wizard\MigrationFromMagento1
 */
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

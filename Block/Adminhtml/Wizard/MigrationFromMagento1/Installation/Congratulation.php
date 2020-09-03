<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Wizard\MigrationFromMagento1\Installation;

use Ess\M2ePro\Block\Adminhtml\Wizard\MigrationFromMagento1\Installation;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Wizard\MigrationFromMagento1\Installation\Congratulation
 */
class Congratulation extends Installation
{
    protected function _construct()
    {
        parent::_construct();

        $this->updateButton('continue', 'label', $this->__('Complete'));
        $this->updateButton('continue', 'class', 'primary');
        $this->updateButton('continue', 'onclick', 'MigrationFromMagento1Obj.congratulationStep();');
    }

    protected function getStep()
    {
        return 'congratulation';
    }
}

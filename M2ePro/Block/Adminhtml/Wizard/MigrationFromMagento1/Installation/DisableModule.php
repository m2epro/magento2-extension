<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Wizard\MigrationFromMagento1\Installation;

use Ess\M2ePro\Block\Adminhtml\Wizard\MigrationFromMagento1\Installation;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Wizard\MigrationFromMagento1\Installation\DisableModule
 */
class DisableModule extends Installation
{
    //########################################

    protected function _construct()
    {
        parent::_construct();

        $wizardUrl = $this->getUrl('m2epro/migrationFromMagento1/prepare');

        $this->updateButton('continue', 'onclick', 'setLocation("'.$wizardUrl.'")');
    }

    protected function getStep()
    {
        return 'disableModule';
    }

    //########################################
}

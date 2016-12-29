<?php

namespace Ess\M2ePro\Block\Adminhtml\Wizard\MigrationFromMagento1\Installation;

use Ess\M2ePro\Block\Adminhtml\Wizard\MigrationFromMagento1\Installation;

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
<?php

namespace Ess\M2ePro\Block\Adminhtml\Wizard\MigrationFromMagento1\Installation;

use Ess\M2ePro\Block\Adminhtml\Wizard\MigrationFromMagento1\Installation;

class Welcome extends Installation
{
    protected function getStep()
    {
        return 'welcome';
    }
}
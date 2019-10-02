<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Wizard\InstallationEbay\Installation;

use Ess\M2ePro\Block\Adminhtml\Wizard\InstallationEbay\Installation;

/**
 * Class Registration
 * @package Ess\M2ePro\Block\Adminhtml\Wizard\InstallationEbay\Installation
 */
class Registration extends Installation
{
    protected function getStep()
    {
        return 'registration';
    }
}

<?php

namespace Ess\M2ePro\Controller\Adminhtml\ControlPanel\Inspection;

use Ess\M2ePro\Controller\Adminhtml\ControlPanel\Main;
use Ess\M2ePro\Helper\Module;
use Magento\Backend\App\Action;

class PhpInfo extends Main
{
    public function execute()
    {
        phpinfo();
    }
}
<?php

namespace Ess\M2ePro\Controller\Adminhtml\ControlPanel;

use Ess\M2ePro\Helper\Module;
use Magento\Backend\App\Action;

class InspectionTab extends Main
{
    public function execute()
    {
        $block = $this->createBlock('ControlPanel\Tabs\Inspection', '');
        $this->setAjaxContent($block);

        return $this->getResult();
    }

}
<?php

namespace Ess\M2ePro\Controller\Adminhtml\ControlPanel;

use Ess\M2ePro\Helper\Module;
use Magento\Backend\App\Action;

class Index extends Main
{
    public function execute()
    {
        $this->init();

        $block = $this->createBlock('ControlPanel\Tabs', '');
        $block->setData('tab', 'summary');
        $this->addContent($block);

        return $this->getResult();
    }

}
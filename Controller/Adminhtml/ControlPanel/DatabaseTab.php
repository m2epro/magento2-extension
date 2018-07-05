<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\ControlPanel;

use Ess\M2ePro\Helper\Module;
use Magento\Backend\App\Action;
use Magento\Framework\App\ResponseInterface;

class DatabaseTab extends Main
{
    public function execute()
    {
        $block = $this->createBlock('ControlPanel\Tabs\Database', '');
        $this->setAjaxContent($block);

        return $this->getResult();
    }
}
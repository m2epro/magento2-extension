<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Order;

use Ess\M2ePro\Controller\Adminhtml\Walmart\Order;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Walmart\Order\Index
 */
class Index extends Order
{
    public function execute()
    {
        $this->init();
        $this->addContent($this->createBlock('Walmart\Order'));
        $this->setPageHelpLink('x/VwBhAQ');

        return $this->getResult();
    }
}

<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Order;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Order;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Amazon\Order\Index
 */
class Index extends Order
{
    public function execute()
    {
        $this->init();
        $this->addContent($this->createBlock('Amazon\Order'));
        $this->setPageHelpLink('x/rgEtAQ');

        return $this->getResult();
    }
}

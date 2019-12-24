<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Order;

use Ess\M2ePro\Controller\Adminhtml\Ebay\Order;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Order\Index
 */
class Index extends Order
{
    public function execute()
    {
        $this->init();
        $this->addContent($this->createBlock('Ebay\Order'));
        $this->setPageHelpLink('x/ngEtAQ');

        return $this->getResultPage();
    }
}

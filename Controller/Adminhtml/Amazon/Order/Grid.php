<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Order;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Order;

/**
 * Class Grid
 * @package Ess\M2ePro\Controller\Adminhtml\Amazon\Order
 */
class Grid extends Order
{
    public function execute()
    {
        $this->setAjaxContent($this->createBlock('Amazon_Order_Grid'));

        return $this->getResult();
    }
}

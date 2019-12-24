<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Log\Order;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Walmart\Log\Order\Grid
 */
class Grid extends \Ess\M2ePro\Controller\Adminhtml\Walmart\Log\Order
{
    //########################################

    public function execute()
    {
        $this->setAjaxContent($this->createBlock('Walmart_Log_Order_Grid'));

        return $this->getResult();
    }

    //########################################
}

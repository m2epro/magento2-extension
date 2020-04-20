<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Walmart\Order
 */
abstract class Order extends Main
{
    //########################################

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Ess_M2ePro::walmart_sales_orders');
    }

    //########################################

    protected function init()
    {
        $this->addCss('order.css');
        $this->addCss('switcher.css');
        $this->addCss('walmart/order/grid.css');

        $this->getResultPage()->getConfig()->getTitle()->prepend($this->__('Sales'));
        $this->getResultPage()->getConfig()->getTitle()->prepend($this->__('Orders'));
    }

    //########################################
}

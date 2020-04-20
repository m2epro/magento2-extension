<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Log\Order;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Log\Order\Grid
 */
class Grid extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Log\Order
{
    //########################################

    public function execute()
    {
        $response = $this->createBlock('Ebay_Log_Order_Grid')->toHtml();
        $this->setAjaxContent($response);

        return $this->getResult();
    }

    //########################################
}

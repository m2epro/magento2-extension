<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Log\Order;

class Grid extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Log\Order
{
    //########################################

    public function execute()
    {
        $response = $this->createBlock('Ebay\Log\Order\Grid')->toHtml();
        $this->setAjaxContent($response);

        return $this->getResult();
    }

    //########################################
}
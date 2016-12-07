<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Log\Order;

class Grid extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Log\Order
{
    //########################################

    public function execute()
    {
        $this->setAjaxContent($this->createBlock('Amazon\Log\Order\Grid'));

        return $this->getResult();
    }

    //########################################
}
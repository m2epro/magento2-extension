<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Log\Order;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Amazon\Log\Order\Grid
 */
class Grid extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Log\Order
{
    //########################################

    public function execute()
    {
        $this->setAjaxContent(
            $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Amazon\Log\Order\Grid::class)
        );

        return $this->getResult();
    }

    //########################################
}

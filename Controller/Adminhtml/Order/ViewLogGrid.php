<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Order;

use Ess\M2ePro\Controller\Adminhtml\Order;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Order\ViewLogGrid
 */
class ViewLogGrid extends Order
{
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        $order = $this->activeRecordFactory->getObjectLoaded('Order', $id);

        $this->getHelper('Data\GlobalData')->setValue('order', $order);

        $grid = $this->createBlock('Order_View_Log_Grid');

        $this->setAjaxContent($grid->toHtml());
        return $this->getResult();
    }
}

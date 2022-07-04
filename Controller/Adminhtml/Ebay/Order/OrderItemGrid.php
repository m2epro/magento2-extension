<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Order;

use Ess\M2ePro\Controller\Adminhtml\Ebay\Order;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Order\OrderItemGrid
 */
class OrderItemGrid extends Order
{
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        /** @var \Ess\M2ePro\Model\Order $order */
        $order = $this->ebayFactory->getObjectLoaded('Order', (int)$id);

        $this->getHelper('Data\GlobalData')->setValue('order', $order);

        $this->setAjaxContent(
            $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Ebay\Order\View\Item::class)->toHtml()
        );

        return $this->getResult();
    }
}

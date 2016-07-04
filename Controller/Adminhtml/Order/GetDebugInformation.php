<?php

namespace Ess\M2ePro\Controller\Adminhtml\Order;

use Ess\M2ePro\Controller\Adminhtml\Order;

class GetDebugInformation extends Order
{
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');

        if (is_null($id)) {
            $this->setAjaxContent('', false);
            return $this->getResult();
        }

        try {
            $order = $this->activeRecordFactory->getObjectLoaded('Order', (int)$id);
        } catch (\Exception $e) {
            $this->setAjaxContent('', false);
            return $this->getResult();
        }

        $this->getHelper('Data\GlobalData')->setValue('order', $order);

        $debugBlock = $this->createBlock('Order\Debug');

        $this->setAjaxContent($debugBlock->toHtml());

        return $this->getResult();
    }
}
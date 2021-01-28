<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Order;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Order;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Amazon\Order\ResendInvoice
 */
class ResendInvoice extends Order
{
    public function execute()
    {
        $orderId = $this->getRequest()->getParam('order_id');
        $documentType = $this->getRequest()->getParam('document_type');

        if (empty($orderId) || empty($documentType)) {
            $this->setAjaxContent('You should provide correct parameters.', false);

            return $this->getResult();
        }

        /** @var \Ess\M2ePro\Model\Order $order */
        $order = $this->amazonFactory->getObjectLoaded('Order', (int)$orderId);
        $order->getLog()->setInitiator(\Ess\M2ePro\Helper\Data::INITIATOR_USER);

        if ($documentType == \Ess\M2ePro\Model\Amazon\Order\Invoice::DOCUMENT_TYPE_INVOICE) {
            $order->getChildObject()->sendInvoice();
            $this->setJsonContent(
                [
                    'msg' => [
                        'type' => 'success',
                        'text' => $this->__('Order Invoice will be sent to Amazon.')
                    ]
                ]
            );
        }

        if ($documentType == \Ess\M2ePro\Model\Amazon\Order\Invoice::DOCUMENT_TYPE_CREDIT_NOTE) {
            $order->getChildObject()->sendCreditmemo();
            $this->setJsonContent(
                [
                    'msg' => [
                        'type' => 'success',
                        'text' => $this->__('Order Credit Memo will be sent to Amazon.')
                    ]
                ]
            );
        }

        return $this->getResult();
    }
}

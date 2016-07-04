<?php

namespace Ess\M2ePro\Controller\Adminhtml\Order;

use Ess\M2ePro\Controller\Adminhtml\Order;

class ReservationPlace extends Order
{
    public function execute()
    {
        $ids = $this->getRequestIds();

        if (count($ids) == 0) {
            $this->messageManager->addError($this->__('Please select Order(s).'));
            $this->_redirect('*/*/index');
            return;
        }

        /** @var $orders \Ess\M2ePro\Model\Order[] */
        $orders = $this->activeRecordFactory->getObject('Order')
            ->getCollection()
            ->addFieldToFilter('id', array('in' => $ids))
            ->addFieldToFilter('reservation_state', array('neq' => \Ess\M2ePro\Model\Order\Reserve::STATE_PLACED))
            ->addFieldToFilter('magento_order_id', array('null' => true));

        try {
            $actionSuccessful = false;

            foreach ($orders as $order) {
                $order->getLog()->setInitiator(\Ess\M2ePro\Helper\Data::INITIATOR_USER);

                if (!$order->isReservable()) {
                    continue;
                }

                if ($order->getReserve()->place()) {
                    $actionSuccessful = true;
                }
            }

            if ($actionSuccessful) {
                $this->messageManager->addSuccess(
                    $this->__('QTY for selected Order(s) was successfully reserved.')
                );
            } else {
                $this->messageManager->addError(
                    $this->__('QTY for selected Order(s) was not reserved.')
                );
            }

        } catch (\Exception $e) {
            $this->messageManager->addError(
                $this->__(
                    'QTY for selected Order(s) was not reserved. Reason: %error_message%',
                    $e->getMessage())
            );
        }

        $this->_redirect($this->_redirect->getRefererUrl());
    }
}
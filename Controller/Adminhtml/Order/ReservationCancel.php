<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Order;

use Ess\M2ePro\Controller\Adminhtml\Order;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Order\ReservationCancel
 */
class ReservationCancel extends Order
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
            ->addFieldToFilter('id', ['in' => $ids])
            ->addFieldToFilter('reservation_state', \Ess\M2ePro\Model\Order\Reserve::STATE_PLACED);

        try {
            $actionSuccessful = false;

            foreach ($orders as $order) {
                $order->getLog()->setInitiator(\Ess\M2ePro\Helper\Data::INITIATOR_USER);

                if ($order->getReserve()->cancel()) {
                    $actionSuccessful = true;
                }
            }

            if ($actionSuccessful) {
                $this->messageManager->addSuccess(
                    $this->__('QTY reserve for selected Order(s) was successfully canceled.')
                );
            } else {
                $this->messageManager->addError(
                    $this->__('QTY reserve for selected Order(s) was not canceled.')
                );
            }
        } catch (\Exception $e) {
            $this->messageManager->addError(
                $this->__(
                    'QTY reserve for selected Order(s) was not canceled. Reason: %error_message%',
                    $e->getMessage()
                )
            );
        }

        $this->_redirect($this->_redirect->getRefererUrl());
    }
}

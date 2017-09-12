<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Connector\Orders\Cancel;

class ProcessingRunner extends \Ess\M2ePro\Model\Connector\Command\Pending\Processing\Runner\Single
{
    // ########################################

    protected function eventBefore()
    {
        $params = $this->getParams();

        /** @var \Ess\M2ePro\Model\Amazon\Processing\Action $processingAction */
        $processingAction = $this->activeRecordFactory->getObject('Amazon\Processing\Action');
        $processingAction->setData(array(
            'account_id'    => $params['account_id'],
            'processing_id' => $this->getProcessingObject()->getId(),
            'related_id'    => $params['change_id'],
            'type'          => \Ess\M2ePro\Model\Amazon\Processing\Action::TYPE_ORDER_CANCEL,
            'request_data'  => $this->getHelper('Data')->jsonEncode($params['request_data']),
            'start_date'    => $params['start_date'],
        ));
        $processingAction->save();
    }

    protected function setLocks()
    {
        parent::setLocks();

        $params = $this->getParams();

        /** @var \Ess\M2ePro\Model\Order $order */
        $order = $this->activeRecordFactory->getObjectLoaded('Order', $params['order_id']);
        $order->addProcessingLock('cancel_order', $this->getProcessingObject()->getId());
    }

    protected function unsetLocks()
    {
        parent::unsetLocks();

        $params = $this->getParams();

        /** @var \Ess\M2ePro\Model\Order $order */
        $order = $this->activeRecordFactory->getObjectLoaded('Order', $params['order_id']);
        $order->deleteProcessingLocks('cancel_order', $this->getProcessingObject()->getId());
    }

    // ########################################
}
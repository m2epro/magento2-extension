<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Connector\Orders\Update;

/**
 * Class \Ess\M2ePro\Model\Amazon\Connector\Orders\Update\ProcessingRunner
 */
class ProcessingRunner extends \Ess\M2ePro\Model\Connector\Command\Pending\Processing\Runner\Single
{
    // ########################################

    protected function eventBefore()
    {
        $params = $this->getParams();

        /** @var \Ess\M2ePro\Model\Amazon\Processing\Action $processingAction */
        $processingAction = $this->activeRecordFactory->getObject('Amazon_Processing_Action');
        $processingAction->setData([
            'account_id'    => $params['account_id'],
            'processing_id' => $this->getProcessingObject()->getId(),
            'related_id'    => $params['change_id'],
            'type'          => \Ess\M2ePro\Model\Amazon\Processing\Action::TYPE_ORDER_UPDATE,
            'request_data'  => $this->getHelper('Data')->jsonEncode($params['request_data']),
            'start_date'    => $params['start_date']
        ]);
        $processingAction->save();
    }

    protected function setLocks()
    {
        parent::setLocks();

        $params = $this->getParams();

        /** @var \Ess\M2ePro\Model\Order $order */
        $order = $this->activeRecordFactory->getObjectLoaded('Order', $params['order_id']);
        $order->addProcessingLock('update_shipping_status', $this->getProcessingObject()->getId());
    }

    protected function unsetLocks()
    {
        parent::unsetLocks();

        $params = $this->getParams();

        /** @var \Ess\M2ePro\Model\Order $order */
        $order = $this->activeRecordFactory->getObjectLoaded('Order', $params['order_id']);
        $order->deleteProcessingLocks('update_shipping_status', $this->getProcessingObject()->getId());
    }

    // ########################################
}

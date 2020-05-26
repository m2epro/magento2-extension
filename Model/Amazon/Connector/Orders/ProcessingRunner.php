<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Connector\Orders;

/**
 * Class \Ess\M2ePro\Model\Amazon\Connector\Orders\ProcessingRunner
 */
class ProcessingRunner extends \Ess\M2ePro\Model\Connector\Command\Pending\Processing\Single\Runner
{
    /** @var \Ess\M2ePro\Model\Amazon\Order\Action\Processing $processingAction */
    protected $processingAction;

    //########################################

    protected function eventBefore()
    {
        $params = $this->getParams();

        /** @var \Ess\M2ePro\Model\Amazon\Order\Action\Processing $processingAction */
        $processingAction = $this->activeRecordFactory->getObject('Amazon_Order_Action_Processing');
        $processingAction->setData(
            [
                'processing_id' => $this->getProcessingObject()->getId(),
                'order_id'      => $params['order_id'],
                'type'          => $params['action_type'],
                'request_data'  => $this->getHelper('Data')->jsonEncode($params['request_data']),
            ]
        );
        $processingAction->save();
    }

    protected function setLocks()
    {
        parent::setLocks();

        $params = $this->getParams();

        /** @var \Ess\M2ePro\Model\Order $order */
        $order = $this->activeRecordFactory->getObjectLoaded('Order', $params['order_id']);
        $order->addProcessingLock($params['lock_name'], $this->getProcessingObject()->getId());
    }

    protected function unsetLocks()
    {
        parent::unsetLocks();

        $params = $this->getParams();

        /** @var \Ess\M2ePro\Model\Order $order */
        $order = $this->activeRecordFactory->getObjectLoaded('Order', $params['order_id']);
        $order->deleteProcessingLocks($params['lock_name'], $this->getProcessingObject()->getId());
    }

    //########################################

    public function complete()
    {
        if ($this->getProcessingAction() && $this->getProcessingAction()->getId()) {
            $this->getProcessingAction()->delete();
        }

        parent::complete();
    }

    //########################################

    public function getProcessingAction()
    {
        if ($this->processingAction !== null) {
            return $this->processingAction;
        }

        $processingActionCollection = $this->activeRecordFactory->getObject('Amazon_Order_Action_Processing')
            ->getCollection();
        $processingActionCollection->addFieldToFilter('processing_id', $this->getProcessingObject()->getId());

        $processingAction = $processingActionCollection->getFirstItem();

        return $processingAction->getId() ? $this->processingAction = $processingAction : null;
    }

    //########################################
}

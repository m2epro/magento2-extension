<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Connector\Order\Cancellation;

/**
 * Class \Ess\M2ePro\Model\Ebay\Connector\Order\Cancellation\Refund
 */
class Refund extends AbstractModel
{
    //########################################

    protected function getCommand()
    {
        return ['orders', 'refund', 'entity'];
    }

    public function getRequestData()
    {
        return [
            'cancel_id'   => $this->params['cancel_id'],
            'refund_date' => $this->params['refund_date'],
        ];
    }

    //########################################

    /**
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function processResponseData()
    {
        $this->order->getLog()->setInitiator($this->orderChange->getCreatorType());
        $this->orderChange->delete();
        $this->order->addSuccessLog('Order is refunded. Status is updated on eBay.');
    }

    //########################################
}

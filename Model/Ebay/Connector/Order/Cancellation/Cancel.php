<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Connector\Order\Cancellation;

/**
 * Class \Ess\M2ePro\Model\Ebay\Connector\Order\Cancellation\Cancel
 */
class Cancel extends AbstractModel
{
    //########################################

    protected function getCommand()
    {
        return ['orders', 'cancel', 'entity'];
    }

    public function getRequestData()
    {
        return $this->orderChange->getParams();
    }

    //########################################

    /**
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function processResponseData()
    {
        $this->order->getLog()->setInitiator($this->orderChange->getCreatorType());

        /** @var \Ess\M2ePro\Model\Connector\Connection\Response\Message\Set $messagesSet */
        $messagesSet = $this->modelFactory->getObject('Connector_Connection_Response_Message_Set');
        $messagesSet->init($this->getResponseMessages());

        foreach ($messagesSet->getEntities() as $message) {
            if ($message->isError()) {
                $this->order->addErrorLog(
                    'eBay order was not canceled. Reason: %msg%',
                    ['msg' => $message->getText()]
                );
            } else {
                $this->order->addWarningLog($message->getText());
            }
        }

        if ($messagesSet->hasErrorEntities()) {
            return;
        }

        if ($this->orderChange->getAction() === \Ess\M2ePro\Model\Order\Change::ACTION_CANCEL) {
            $this->orderChange->delete();
        }

        if ($this->responseData['result'] && $this->responseData['cancel_id'] !== null) {
            $this->order->getChildObject()->setData('cancellation_status', 1);
            $this->order->getChildObject()->save();
            $this->order->addSuccessLog('Order is canceled. Status is updated on eBay.');
        }
    }

    //########################################
}

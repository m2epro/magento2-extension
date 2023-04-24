<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Order\Action\Handler;

use Ess\M2ePro\Model\Walmart\Order\Item as OrderItem;

/**
 * Class \Ess\M2ePro\Model\Walmart\Order\Action\Handler\Do not change order status
 */
class Acknowledge extends \Ess\M2ePro\Model\Walmart\Order\Action\Handler\AbstractModel
{
    //########################################

    public function isNeedProcess()
    {
        if (!$this->getWalmartOrder()->isCreated()) {
            return false;
        }

        if (!$this->getWalmartOrder()->canAcknowledgeOrder()) {
            return false;
        }

        return true;
    }

    //########################################

    protected function getServerCommand()
    {
        return ['orders', 'acknowledge', 'entity'];
    }

    protected function getRequestData()
    {
        return [
            'channel_order_id' => $this->getWalmartOrder()->getWalmartOrderId(),
        ];
    }

    protected function processResult(array $responseData)
    {
        if (!isset($responseData['result']) || !$responseData['result']) {
            $this->processError();

            return;
        }

        $this->getOrder()->addSuccessLog(
            $this->helperFactory->getObject('Module\Translation')->__('Order was acknowledged.')
        );
    }

    /**
     * @param \Ess\M2ePro\Model\Connector\Connection\Response\Message[] $messages
     */
    protected function processError(array $messages = [])
    {
        if (empty($messages)) {
            /** @var \Ess\M2ePro\Model\Connector\Connection\Response\Message $message */
            $message = $this->modelFactory->getObject('Connector_Connection_Response_Message');
            $message->initFromPreparedData(
                $this->helperFactory->getObject('Module\Translation')->__(
                    'Order was not acknowledged due to Walmart error.'
                ),
                \Ess\M2ePro\Model\Connector\Connection\Response\Message::TYPE_ERROR
            );

            $messages = [$message];
        }

        foreach ($messages as $message) {
            $this->getOrder()->getLog()->addServerResponseMessage($this->getOrder(), $message);
        }
    }

    //########################################
}

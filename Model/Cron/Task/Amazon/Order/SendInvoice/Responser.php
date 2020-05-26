<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task\Amazon\Order\SendInvoice;

/**
 * Class \Ess\M2ePro\Model\Cron\Task\Amazon\Order\SendInvoice\Responser
 */
class Responser extends \Ess\M2ePro\Model\Amazon\Connector\Orders\SendInvoice\ItemsResponser
{
    /** @var \Ess\M2ePro\Model\Order $order */
    protected $order = [];

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\Connector\Connection\Response $response,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $params = []
    ) {
        parent::__construct($amazonFactory, $activeRecordFactory, $response, $helperFactory, $modelFactory, $params);

        $this->order = $this->amazonFactory->getObjectLoaded('Order', $params['order']['order_id']);
    }

    //########################################

    public function failDetected($messageText)
    {
        parent::failDetected($messageText);

        /** @var \Ess\M2ePro\Model\Order\Change $orderChange */
        $orderChange = $this->activeRecordFactory->getObject('Order\Change')->load($this->params['order']['change_id']);
        $this->order->getLog()->setInitiator($orderChange->getCreatorType());
        $this->order->addErrorLog('Amazon Order invoice was not send. Reason: %msg%', ['msg' => $messageText]);
    }

    /**
     * @return bool
     */
    protected function isNeedProcessResponse()
    {
        if (!parent::isNeedProcessResponse()) {
            return false;
        }

        if ($this->getResponse()->getMessages()->hasErrorEntities()) {
            return false;
        }

        return true;
    }

    /**
     * @param array $messages
     * @return void|null
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function processResponseMessages(array $messages = array())
    {
        parent::processResponseMessages();

        /** @var \Ess\M2ePro\Model\Order\Change $orderChange */
        $orderChange = $this->activeRecordFactory->getObject('Order\Change')->load($this->params['order']['change_id']);
        $this->order->getLog()->setInitiator($orderChange->getCreatorType());

        foreach ($this->getResponse()->getMessages()->getEntities() as $message) {
            if ($message->isError()) {
                $this->logErrorMessage($message);
            } else {
                $this->order->addWarningLog($message->getText());
            }
        }
    }

    //########################################

    protected function processResponseData()
    {
        $this->activeRecordFactory->getObject('Order\Change')->getResource()
            ->deleteByIds([$this->params['order']['change_id']]);

        $responseData = $this->getPreparedResponseData();

        // Check separate messages
        //----------------------
        $isFailed = false;

        /** @var \Ess\M2ePro\Model\Connector\Connection\Response\Message\Set $messagesSet */
        $messagesSet = $this->modelFactory->getObject('Connector_Connection_Response_Message_Set');
        $messagesSet->init($responseData['messages']);

        foreach ($messagesSet->getEntities() as $message) {
            $this->order->getLog()->setInitiator(\Ess\M2ePro\Helper\Data::INITIATOR_EXTENSION);
            if ($message->isError()) {
                $isFailed = true;
                $this->logErrorMessage($message);
            } else {
                $this->order->addWarningLog($message->getText());
            }
        }

        if ($isFailed) {
            return;
        }

        $this->order->getLog()->setInitiator(\Ess\M2ePro\Helper\Data::INITIATOR_EXTENSION);

        if ($this->params['order']['document_type'] == \Ess\M2ePro\Model\Amazon\Order::DOCUMENT_TYPE_INVOICE) {
            $this->order->getChildObject()->setData('is_invoice_sent', 1)->save();
            $this->order->addSuccessLog(
                'Invoice #%document_number% was sent.',
                [
                    'document_number' => $this->params['order']['document_number'],
                ]
            );
        } elseif ($this->params['order']['document_type'] ==
            \Ess\M2ePro\Model\Amazon\Order::DOCUMENT_TYPE_CREDIT_NOTE) {
            $this->order->getChildObject()->setData('is_credit_memo_sent', 1)->save();
            $this->order->addSuccessLog(
                'Credit Memo #%document_number% was sent.',
                [
                    'document_number' => $this->params['order']['document_number'],
                ]
            );
        }
    }

    /**
     * @param \Ess\M2ePro\Model\Response\Message $message
     */
    protected function logErrorMessage(\Ess\M2ePro\Model\Response\Message $message)
    {
        if ($this->params['order']['document_type'] == \Ess\M2ePro\Model\Amazon\Order::DOCUMENT_TYPE_INVOICE) {
            $this->order->addErrorLog(
                'Invoice #%document_number% was not sent. Reason: %msg%',
                [
                    'document_number' => $this->params['order']['document_number'],
                    'msg' => $message->getText()
                ]
            );
        } elseif ($this->params['order']['document_type'] ==
            \Ess\M2ePro\Model\Amazon\Order::DOCUMENT_TYPE_CREDIT_NOTE) {
            $this->order->addErrorLog(
                'Credit Memo #%document_number% was not sent. Reason: %msg%',
                [
                    'document_number' => $this->params['order']['document_number'],
                    'msg' => $message->getText()
                ]
            );
        }
    }

    //########################################
}

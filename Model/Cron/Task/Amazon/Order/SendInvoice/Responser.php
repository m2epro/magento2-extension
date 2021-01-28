<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task\Amazon\Order\SendInvoice;

use Ess\M2ePro\Model\Amazon\Order\Invoice as AmazonOrderInvoice;

/**
 * Class \Ess\M2ePro\Model\Cron\Task\Amazon\Order\SendInvoice\Responser
 */
class Responser extends \Ess\M2ePro\Model\Amazon\Connector\Orders\SendInvoice\ItemsResponser
{
    /** @var \Ess\M2ePro\Model\Order */
    protected $order;

    /** @var \Ess\M2ePro\Model\Order\Change */
    protected $orderChange;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\Connector\Connection\Response $response,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $params = []
    ) {
        parent::__construct(
            $response,
            $helperFactory,
            $modelFactory,
            $amazonFactory,
            $walmartFactory,
            $ebayFactory,
            $activeRecordFactory,
            $params
        );

        $this->order = $this->amazonFactory->getObjectLoaded('Order', $params['order']['order_id']);
        $this->orderChange = $this->activeRecordFactory->getObject('Order\Change')->load($params['order']['change_id']);
    }

    //########################################

    public function failDetected($messageText)
    {
        parent::failDetected($messageText);

        $this->order->getLog()->setInitiator($this->orderChange->getCreatorType());
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
    protected function processResponseMessages(array $messages = [])
    {
        parent::processResponseMessages();

        $this->order->getLog()->setInitiator($this->orderChange->getCreatorType());

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
        $this->order->getLog()->setInitiator($this->orderChange->getCreatorType());
        $responseData = $this->getPreparedResponseData();

        /** @var \Ess\M2ePro\Model\Connector\Connection\Response\Message\Set $messagesSet */
        $messagesSet = $this->modelFactory->getObject('Connector_Connection_Response_Message_Set');
        $messagesSet->init($responseData['messages']);

        foreach ($messagesSet->getEntities() as $message) {
            if ($message->isError()) {
                $this->logErrorMessage($message);
            } else {
                $this->order->addWarningLog($message->getText());
            }
        }

        if ($messagesSet->hasErrorEntities()) {
            return;
        }

        $this->orderChange->delete();

        if ($this->params['order']['document_type'] == AmazonOrderInvoice::DOCUMENT_TYPE_INVOICE) {
            $this->order->getChildObject()->setData('is_invoice_sent', 1)->save();
            $this->order->addSuccessLog(
                'Invoice #%document_number% was sent.',
                [
                    'document_number' => $this->params['order']['document_number']
                ]
            );
        } elseif ($this->params['order']['document_type'] == AmazonOrderInvoice::DOCUMENT_TYPE_CREDIT_NOTE) {
            $this->order->getChildObject()->setData('is_credit_memo_sent', 1)->save();
            $this->order->addSuccessLog(
                'Credit Memo #%document_number% was sent.',
                [
                    'document_number' => $this->params['order']['document_number']
                ]
            );
        }
    }

    /**
     * @param \Ess\M2ePro\Model\Response\Message $message
     */
    protected function logErrorMessage(\Ess\M2ePro\Model\Response\Message $message)
    {
        if ($this->params['order']['document_type'] == AmazonOrderInvoice::DOCUMENT_TYPE_INVOICE) {
            $this->order->addErrorLog(
                'Invoice #%document_number% was not sent. Reason: %msg%',
                [
                    'document_number' => $this->params['order']['document_number'],
                    'msg' => $message->getText()
                ]
            );
        } elseif ($this->params['order']['document_type'] == AmazonOrderInvoice::DOCUMENT_TYPE_CREDIT_NOTE) {
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

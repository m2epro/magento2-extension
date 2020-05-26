<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task\Amazon\Order\Cancel;

/**
 * Class \Ess\M2ePro\Model\Cron\Task\Amazon\Order\Cancel\Responser
 */
class Responser extends \Ess\M2ePro\Model\Amazon\Connector\Orders\Cancel\ItemsResponser
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

    /**
     * @param $messageText
     * @return void|null
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function failDetected($messageText)
    {
        parent::failDetected($messageText);

        /** @var \Ess\M2ePro\Model\Order\Change $orderChange */
        $orderChange = $this->activeRecordFactory->getObject('Order\Change')->load($this->params['order']['change_id']);
        $this->order->getLog()->setInitiator($orderChange->getCreatorType());
        $this->order->addErrorLog('Amazon Order was not cancelled. Reason: %msg%', ['msg' => $messageText]);
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
                $this->order->addErrorLog(
                    'Amazon Order was not cancelled. Reason: %msg%',
                    array('msg' => $message->getText())
                );
            } else {
                $this->order->addWarningLog($message->getText());
            }
        }
    }

    //########################################

    /**
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function processResponseData()
    {
        /** @var \Ess\M2ePro\Model\Order\Change $orderChange */
        $orderChange = $this->activeRecordFactory->getObject('Order\Change')->load($this->params['order']['change_id']);
        $this->order->getLog()->setInitiator($orderChange->getCreatorType());
        $orderChange->delete();

        $responseData = $this->getPreparedResponseData();

        // Check separate messages
        //----------------------
        $isFailed = false;

        /** @var \Ess\M2ePro\Model\Connector\Connection\Response\Message\Set $messagesSet */
        $messagesSet = $this->modelFactory->getObject('Connector_Connection_Response_Message_Set');
        $messagesSet->init($responseData['messages']);

        foreach ($messagesSet->getEntities() as $message) {
            if ($message->isError()) {
                $isFailed = true;

                $this->order->addErrorLog(
                    'Amazon Order was not cancelled. Reason: %msg%',
                    ['msg' => $message->getText()]
                );
            } else {
                $this->order->addWarningLog($message->getText());
            }
        }

        if ($isFailed) {
            return;
        }

        $this->order->addSuccessLog('Amazon Order was cancelled.');
    }

    //########################################
}

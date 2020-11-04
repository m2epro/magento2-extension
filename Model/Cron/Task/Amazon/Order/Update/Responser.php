<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task\Amazon\Order\Update;

/**
 * Class \Ess\M2ePro\Model\Cron\Task\Amazon\Order\Update\Responser
 */
class Responser extends \Ess\M2ePro\Model\Amazon\Connector\Orders\Update\ItemsResponser
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

    /**
     * @param $messageText
     * @return void|null
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function failDetected($messageText)
    {
        parent::failDetected($messageText);

        $this->order->getLog()->setInitiator($this->orderChange->getCreatorType());
        $this->order->addErrorLog('Amazon Order status was not updated. Reason: %msg%', ['msg' => $messageText]);
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
                $this->order->addErrorLog(
                    'Amazon Order status was not updated. Reason: %msg%',
                    ['msg' => $message->getText()]
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
        $this->order->getLog()->setInitiator($this->orderChange->getCreatorType());
        $responseData = $this->getResponse()->getResponseData();

        /** @var \Ess\M2ePro\Model\Connector\Connection\Response\Message\Set $messagesSet */
        $messagesSet = $this->modelFactory->getObject('Connector_Connection_Response_Message_Set');
        $messagesSet->init($responseData['messages']);

        foreach ($messagesSet->getEntities() as $message) {
            if ($message->isError()) {
                $this->order->addErrorLog(
                    'Amazon Order status was not updated. Reason: %msg%',
                    ['msg' => $message->getText()]
                );
            } else {
                $this->order->addWarningLog($message->getText());
            }
        }

        if ($messagesSet->hasErrorEntities()) {
            return;
        }

        $this->orderChange->delete();
        $this->order->addSuccessLog('Amazon Order status was updated to Shipped.');

        if (empty($this->params['order']['tracking_number']) || empty($this->params['order']['carrier_name'])) {
            return;
        }

        $this->order->addSuccessLog(
            'Tracking number "%num%" for "%code%" has been sent to Amazon.',
            [
                '!num' => $this->params['order']['tracking_number'],
                'code' => $this->params['order']['carrier_name']
            ]
        );
    }

    //########################################
}

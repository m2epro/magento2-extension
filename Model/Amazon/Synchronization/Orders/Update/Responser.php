<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2016 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Synchronization\Orders\Update;

class Responser extends \Ess\M2ePro\Model\Amazon\Connector\Orders\Update\ItemsResponser
{
    /** @var \Ess\M2ePro\Model\Order $order */
    private $order = NULL;

    protected $activeRecordFactory;

    // ########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Model\Connector\Connection\Response $response,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $params = array()
    )
    {
        $this->activeRecordFactory = $activeRecordFactory;
        parent::__construct($amazonFactory, $response, $helperFactory, $modelFactory, $params);

        $this->order = $this->activeRecordFactory->getObjectLoaded('Order', $this->params['order']['order_id']);
    }

    public function failDetected($messageText)
    {
        parent::failDetected($messageText);

        $this->order->getLog()->setInitiator(\Ess\M2ePro\Helper\Data::INITIATOR_EXTENSION);
        $this->order->addErrorLog('Amazon Order status was not updated. Reason: %msg%', array('msg' => $messageText));
    }

    // ########################################

    protected function processResponseData()
    {
        $this->activeRecordFactory->getObject('Order\Change')->getResource()
             ->deleteByIds(array($this->params['order']['change_id']));

        $responseData = $this->getResponse()->getResponseData();

        // Check separate messages
        //----------------------
        $isFailed = false;

        /** @var \Ess\M2ePro\Model\Connector\Connection\Response\Message\Set $messagesSet */
        $messagesSet = $this->modelFactory->getObject('Connector\Connection\Response\Message\Set');
        $messagesSet->init($responseData['messages']);

        foreach ($messagesSet->getEntities() as $message) {

            $this->order->getLog()->setInitiator(\Ess\M2ePro\Helper\Data::INITIATOR_EXTENSION);

            if ($message->isError()) {
                $isFailed = true;
                $this->order->addErrorLog(
                    'Amazon Order status was not updated. Reason: %msg%',
                    array('msg' => $message->getText())
                );
            } else {
                $this->order->addWarningLog($message->getText());
            }
        }
        //----------------------

        if ($isFailed) {
            return;
        }

        //----------------------

        $this->order->getLog()->setInitiator(\Ess\M2ePro\Helper\Data::INITIATOR_EXTENSION);
        $this->order->addSuccessLog('Amazon Order status was updated to Shipped.');

        if (empty($requestData['tracking_number']) || empty($requestData['carrier_name'])) {
            return;
        }

        $this->order->addSuccessLog(
            'Tracking number "%num%" for "%code%" has been sent to Amazon.',
            [
                '!num' => $requestData['tracking_number'],
                'code' => $requestData['carrier_name']
            ]
        );
        //----------------------
    }

    // ########################################
}
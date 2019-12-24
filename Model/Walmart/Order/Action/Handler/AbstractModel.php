<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Order\Action\Handler;

/**
 * Class \Ess\M2ePro\Model\Walmart\Order\Action\Handler\AbstractModel
 */
abstract class AbstractModel extends \Ess\M2ePro\Model\AbstractModel
{
    /** @var \Ess\M2ePro\Model\Order $order */
    protected $order = null;

    /** @var \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory */
    protected $walmartFactory = null;

    /** @var \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory */
    protected $activeRecordFactory = null;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $data = []
    ) {
        $this->walmartFactory = $walmartFactory;
        $this->activeRecordFactory = $activeRecordFactory;

        parent::__construct($helperFactory, $modelFactory, $data);
    }

    //########################################

    public function setOrder(\Ess\M2ePro\Model\Order $order)
    {
        $this->order = $order;
        return $this;
    }

    //########################################

    public function process()
    {
        if (!$this->isNeedProcess()) {
            return;
        }

        /** @var \Ess\M2ePro\Model\Walmart\Connector\Dispatcher $dispatcher */
        $dispatcher = $this->modelFactory->getObject('Walmart_Connector_Dispatcher');

        $serverCommand = $this->getServerCommand();

        $connector = $dispatcher->getVirtualConnector(
            $serverCommand[0],
            $serverCommand[1],
            $serverCommand[2],
            $this->getRequestData(),
            null,
            $this->order->getAccount()
        );

        try {
            $dispatcher->process($connector);
        } catch (\Exception $exception) {
            $this->getHelper('Module\Exception')->process($exception);

            $message = $this->modelFactory->getObject('Connector_Connection_Response_Message');
            $message->initFromException($exception);

            $this->processError([$message]);

            return;
        }

        $responseData = $connector->getResponseData();

        if (empty($responseData)) {
            $this->processError($connector->getResponse()->getMessages()->getEntities());
            return;
        }

        $this->processResult($responseData);
    }

    //########################################

    abstract public function isNeedProcess();

    //########################################

    abstract protected function getServerCommand();

    abstract protected function getRequestData();

    abstract protected function processResult(array $responseData);

    /**
     * @param \Ess\M2ePro\Model\Connector\Connection\Response\Message[] $messages
     */
    abstract protected function processError(array $messages = []);

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Order
     */
    protected function getOrder()
    {
        return $this->order;
    }

    /**
     * @return \Ess\M2ePro\Model\Walmart\Order
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function getWalmartOrder()
    {
        return $this->getOrder()->getChildObject();
    }

    //########################################
}

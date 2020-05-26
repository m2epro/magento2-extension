<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Connector\Order\Update;

/**
 * Class \Ess\M2ePro\Model\Ebay\Connector\Order\Update\Shipping
 */
class Shipping extends \Ess\M2ePro\Model\Ebay\Connector\Order\Update\AbstractModel
{
    private $carrierCode = null;
    private $trackingNumber = null;

    //########################################

    /**
     * @param $action
     * @return $this|AbstractModel
     */
    public function setAction($action)
    {
        parent::setAction($action);

        if ($this->action == \Ess\M2ePro\Model\Ebay\Connector\Order\Dispatcher::ACTION_SHIP_TRACK) {
            $this->carrierCode    = $this->params['carrier_code'];
            $this->trackingNumber = $this->params['tracking_number'];
        }

        return $this;
    }

    //########################################

    /**
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function isNeedSendRequest()
    {
        if (!$this->order->getChildObject()->canUpdateShippingStatus($this->params)) {
            return false;
        }

        return parent::isNeedSendRequest();
    }

    //########################################

    /**
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function getRequestData()
    {
        $requestData = parent::getRequestData();

        if ($this->action == \Ess\M2ePro\Model\Ebay\Connector\Order\Dispatcher::ACTION_SHIP_TRACK) {
            $requestData['carrier_code'] = $this->carrierCode;
            $requestData['tracking_number'] = $this->trackingNumber;
        }

        return $requestData;
    }

    //########################################

    /**
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function prepareResponseData()
    {
        if ($this->getResponse()->isResultError()) {
            return;
        }

        /** @var \Ess\M2ePro\Model\Order\Change $orderChange */
        $orderChange = $this->activeRecordFactory->getObject('Order\Change')->load($this->getOrderChangeId());
        $this->order->getLog()->setInitiator($orderChange->getCreatorType());

        $responseData = $this->getResponse()->getResponseData();

        if (!isset($responseData['result']) || !$responseData['result']) {
            $this->order->addErrorLog(
                'Shipping Status for eBay Order was not updated. Reason: eBay Failure.'
            );

            return;
        }

        if ($this->action == \Ess\M2ePro\Model\Ebay\Connector\Order\Dispatcher::ACTION_SHIP_TRACK) {
            $this->order->addSuccessLog(
                'Tracking number "%num%" for "%code%" has been sent to eBay.',
                [
                    '!num'  => $this->trackingNumber,
                    '!code' => $this->carrierCode
                ]
            );
        }

        if (!$this->order->getChildObject()->isShippingCompleted()) {
            $this->order->addSuccessLog(
                'Shipping Status for eBay Order was updated to Shipped.'
            );
        }

        $orderChange->delete();
    }

    //########################################
}

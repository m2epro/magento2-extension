<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Connector\Order\Update;

/**
 * Class \Ess\M2ePro\Model\Ebay\Connector\Order\Update\Payment
 */
class Payment extends \Ess\M2ePro\Model\Ebay\Connector\Order\Update\AbstractModel
{
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
                'Payment Status for eBay Order was not updated. Reason: eBay Failure.'
            );
            return;
        }

        $this->order->addSuccessLog('Payment Status for eBay Order was updated to Paid.');

        if (isset($responseData['is_already_paid']) && $responseData['is_already_paid']) {
            $this->order->setData('payment_status', \Ess\M2ePro\Model\Ebay\Order::PAYMENT_STATUS_COMPLETED)->save();
            $this->order->updateMagentoOrderStatus();
        }

        $orderChange->delete();
    }

    //########################################
}

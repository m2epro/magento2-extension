<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Connector\Order\Update;

class Payment extends \Ess\M2ePro\Model\Ebay\Connector\Order\Update\AbstractModel
{
    // M2ePro\TRANSLATIONS
    // Payment Status for eBay Order was not updated. Reason: eBay Failure.
    // Payment Status for eBay Order was updated to Paid.

    // ########################################

    protected function prepareResponseData()
    {
        if ($this->getResponse()->isResultError()) {
            return;
        }

        $responseData = $this->getResponse()->getResponseData();

        if (!isset($responseData['result']) || !$responseData['result']) {
            $this->order->addErrorLog(
                'Payment Status for eBay Order was not updated. Reason: eBay Failure.'
            );
            return;
        }

        $this->order->addSuccessLog('Payment Status for eBay Order was updated to Paid.');

        $this->activeRecordFactory->getObject('Order\Change')->getResource()
            ->deleteByOrderAction($this->order->getId(),\Ess\M2ePro\Model\Order\Change::ACTION_UPDATE_PAYMENT);
    }

    // ########################################
}
<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Connector\OrderItem\Add;

class Dispute extends \Ess\M2ePro\Model\Ebay\Connector\Command\RealTime
{
    // M2ePro\TRANSLATIONS
    // Dispute cannot be opened. Reason: Dispute explanation is not defined.
    // Dispute cannot be opened. Reason: Dispute reason is not defined.
    // Unpaid Item Process was not open for Item #%id%. Reason: %msg%
    // Unpaid Item Process was not open for Item #%id%. Reason: eBay failure. Please try again later.
    // Unpaid Item Process for Item #%id% has been initiated.

    const DISPUTE_EXPLANATION_BUYER_HAS_NOT_PAID = 'BuyerNotPaid';

    /** @var $orderItem \Ess\M2ePro\Model\Order\Item */
    private $orderItem;

    // ########################################

    public function setOrderItem(\Ess\M2ePro\Model\Order\Item $orderItem)
    {
        $this->orderItem = $orderItem;
        $this->account = $orderItem->getOrder()->getAccount();

        return $this;
    }

    // ########################################

    protected function getCommand()
    {
        return array('dispute', 'add', 'entity');
    }

    protected function isNeedSendRequest()
    {
        if (empty($this->params['explanation'])) {
            $this->orderItem->getOrder()->addErrorLog(
                'Dispute cannot be opened. Reason: Dispute explanation is not defined.'
            );

            return false;
        }

        if (empty($this->params['reason'])) {
            $this->orderItem->getOrder()->addErrorLog(
                'Dispute cannot be opened. Reason: Dispute reason is not defined.'
            );

            return false;
        }

        return true;
    }

    protected function getRequestData()
    {
        $requestData = array(
            'item_id'        => $this->orderItem->getChildObject()->getItemId(),
            'transaction_id' => $this->orderItem->getChildObject()->getTransactionId(),
            'explanation'    => $this->params['explanation'],
            'reason'         => $this->params['reason']
        );

        return $requestData;
    }

    protected function validateResponse()
    {
        return true;
    }

    public function process()
    {
        if (!$this->isNeedSendRequest()) {
            return;
        }

        parent::process();

        foreach ($this->getResponse()->getMessages()->getEntities() as $message) {
            if (!$message->isError()) {
                continue;
            }

            $this->orderItem->getOrder()->addErrorLog(
                'Unpaid Item Process was not open for Item #%id%. Reason: %msg%', array(
                    '!id' => $this->orderItem->getChildObject()->getItemId(),
                    'msg' => $message->getText()
                )
            );

            if ((in_array($message->getCode(), array(16207, 16212)))) {
                $this->orderItem->setData(
                    'unpaid_item_process_state',\Ess\M2ePro\Model\Ebay\Order\Item::UNPAID_ITEM_PROCESS_OPENED
                );
                $this->orderItem->save();
            }
        }
    }

    protected function prepareResponseData()
    {
        if ($this->getResponse()->isResultError()) {
            return;
        }

        $responseData = $this->getResponse()->getResponseData();

        if (empty($responseData['dispute_id'])) {
            $log = 'Unpaid Item Process was not open for Item #%id%. Reason: eBay failure. Please try again later.';
            $this->orderItem->getOrder()->addErrorLog($log, array(
                '!id' => $this->orderItem->getChildObject()->getItemId()
            ));
            return;
        }

        $this->orderItem->getChildObject()->setData(
            'unpaid_item_process_state',\Ess\M2ePro\Model\Ebay\Order\Item::UNPAID_ITEM_PROCESS_OPENED
        );
        $this->orderItem->getChildObject()->save();

        $this->orderItem->getOrder()->addSuccessLog('Unpaid Item Process for Item #%id% has been initiated.', array(
            '!id' => $this->orderItem->getChildObject()->getItemId()
        ));
    }

    // ########################################
}
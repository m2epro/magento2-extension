<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Magento\Order;

use Ess\M2ePro\Model\AbstractModel;

/**
 * Class \Ess\M2ePro\Model\Magento\Order\PaymentTransaction
 */
class PaymentTransaction extends AbstractModel
{
    /** @var $magentoOrder \Magento\Sales\Model\Order */
    protected $magentoOrder = null;

    /** @var $transaction \Magento\Sales\Model\Order\Payment\Transaction */
    protected $transaction = null;

    //########################################

    /**
     * @param \Magento\Sales\Model\Order $magentoOrder
     * @return $this
     */
    public function setMagentoOrder(\Magento\Sales\Model\Order $magentoOrder)
    {
        $this->magentoOrder = $magentoOrder;
        return $this;
    }

    //########################################

    public function getPaymentTransaction()
    {
        return $this->transaction;
    }

    //########################################

    public function buildPaymentTransaction()
    {
        $payment = $this->magentoOrder->getPayment();

        if ($payment === false) {
            return;
        }

        $transactionType = \Magento\Sales\Model\Order\Payment\Transaction::TYPE_CAPTURE;
        if ($this->getData('sum') < 0) {
            $transactionType = \Magento\Sales\Model\Order\Payment\Transaction::TYPE_REFUND;
        }

        $existTransaction = $payment->getTransaction($this->getData('transaction_id'));

        if ($existTransaction && $existTransaction->getTxnType() == $transactionType) {
            return null;
        }

        $payment->setTransactionId($this->getData('transaction_id'));
        $this->transaction = $payment->addTransaction($transactionType);

        if (defined('\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS')) {
            $this->unsetData('transaction_id');
            $this->transaction->setAdditionalInformation(
                \Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS,
                $this->getData()
            );
        }

        $this->transaction->save();
    }

    //########################################
}

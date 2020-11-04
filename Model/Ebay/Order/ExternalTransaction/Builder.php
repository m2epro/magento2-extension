<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Order\ExternalTransaction;

use Ess\M2ePro\Model\AbstractModel;

/**
 * Class \Ess\M2ePro\Model\Ebay\Order\ExternalTransaction\Builder
 */
class Builder extends AbstractModel
{
    private $activeRecordFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $data = []
    ) {
        $this->activeRecordFactory = $activeRecordFactory;
        parent::__construct($helperFactory, $modelFactory, $data);
    }

    //########################################

    public function initialize(array $data)
    {
        // Init general data
        // ---------------------------------------
        $this->setData('order_id', $data['order_id']);
        $this->setData('transaction_id', $data['transaction_id']);
        $this->setData('transaction_date', $data['transaction_date']);
        $this->setData('fee', (float)$data['fee']);
        $this->setData('sum', (float)$data['sum']);
        $this->setData('is_refund', (int)$data['is_refund']);
        // ---------------------------------------
    }

    //########################################

    public function process()
    {
        /** @var \Ess\M2ePro\Model\Ebay\Order\ExternalTransaction $transaction */
        $transaction = $this->activeRecordFactory->getObject('Ebay_Order_ExternalTransaction')
            ->getCollection()
            ->addFieldToFilter('order_id', $this->getData('order_id'))
            ->addFieldToFilter('transaction_id', $this->getData('transaction_id'))
            ->getFirstItem();

        foreach ($this->getData() as $key => $value) {
            if ($transaction->getId() && (!$transaction->hasData($key) || $transaction->getData($key) == $value)) {
                continue;
            }

            if ($key === 'transaction_date') {
                $newDate = new \DateTime($value, new \DateTimeZone('UTC'));
                if ($newDate->format('Y-m-d H:i:s') === $transaction->getData($key)) {
                    continue;
                }
            }

            $transaction->addData($this->getData());
            $transaction->save();
            break;
        }

        return $transaction;
    }

    //########################################
}

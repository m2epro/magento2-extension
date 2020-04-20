<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Order\CreditMemo;

/**
 * Handles credit memos, created by seller in admin panel
 */
abstract class Handler extends \Ess\M2ePro\Model\AbstractModel
{
    const HANDLE_RESULT_FAILED    = -1;
    const HANDLE_RESULT_SKIPPED   = 0;
    const HANDLE_RESULT_SUCCEEDED = 1;

    //########################################

    abstract public function handle(\Ess\M2ePro\Model\Order $order, \Magento\Sales\Model\Order\Creditmemo $creditmemo);

    //########################################

    public function factory($component)
    {
        $handler = null;

        switch ($component) {
            case \Ess\M2ePro\Helper\Component\Amazon::NICK:
                $handler = $this->modelFactory->getObject('Amazon_Order_CreditMemo_Handler');
                break;
            case \Ess\M2ePro\Helper\Component\Walmart::NICK:
                $handler = $this->modelFactory->getObject('Walmart_Order_CreditMemo_Handler');
                break;
        }

        if (!$handler) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Credit Memo handler not found.');
        }

        return $handler;
    }

    //########################################
}

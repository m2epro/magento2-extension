<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Order\Creditmemo;

use Magento\Sales\Model\Order\Creditmemo;

/**
 * Handles credit memos, created by seller in admin panel
 */
abstract class Handler extends \Ess\M2ePro\Model\AbstractModel
{
    const HANDLE_RESULT_FAILED    = -1;
    const HANDLE_RESULT_SKIPPED   = 0;
    const HANDLE_RESULT_SUCCEEDED = 1;

    //########################################

    /**
     * @param \Ess\M2ePro\Model\Order $order
     * @param Creditmemo $creditmemo
     * @return int
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function handle(\Ess\M2ePro\Model\Order $order, Creditmemo $creditmemo)
    {
        if ($order->getComponentMode() !== $this->getComponentMode()) {
            throw new \InvalidArgumentException('Invalid component mode.');
        }

        if (!$order->getChildObject()->canRefund()) {
            return self::HANDLE_RESULT_SKIPPED;
        }

        $items = $this->getItemsToRefund($order, $creditmemo);
        return $order->getChildObject()->refund($items) ? self::HANDLE_RESULT_SUCCEEDED : self::HANDLE_RESULT_FAILED;
    }

    abstract protected function getItemsToRefund(\Ess\M2ePro\Model\Order $order, Creditmemo $creditmemo);
    abstract protected function getComponentMode();

    //########################################
}

<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Order;

use Ess\M2ePro\Model\Walmart\Order\Item as OrderItem;

/**
 * Class \Ess\M2ePro\Model\Walmart\Order\Helper
 */
class Helper extends \Ess\M2ePro\Model\AbstractModel
{
    //########################################

    /**
     * @param array $itemsStatuses
     * @return int
     */
    public function getOrderStatus(array $itemsStatuses)
    {
        $isStatusSame = count(array_unique($itemsStatuses)) == 1;
        $hasAcknowledgedItems = in_array(OrderItem::STATUS_ACKNOWLEDGED, $itemsStatuses);
        $hasShippedItems = in_array(OrderItem::STATUS_SHIPPED, $itemsStatuses);

        if ($hasAcknowledgedItems && $hasShippedItems) {
            return \Ess\M2ePro\Model\Walmart\Order::STATUS_SHIPPED_PARTIALLY;
        }

        if (!$isStatusSame && $hasShippedItems) {
            return \Ess\M2ePro\Model\Walmart\Order::STATUS_SHIPPED;
        }

        if (!$isStatusSame) {
            return \Ess\M2ePro\Model\Walmart\Order::STATUS_UNSHIPPED;
        }

        $resultStatus = null;

        switch (array_shift($itemsStatuses)) {
            case \Ess\M2ePro\Model\Walmart\Order\Item::STATUS_CREATED:
                $resultStatus = \Ess\M2ePro\Model\Walmart\Order::STATUS_CREATED;
                break;

            case \Ess\M2ePro\Model\Walmart\Order\Item::STATUS_ACKNOWLEDGED:
                $resultStatus = \Ess\M2ePro\Model\Walmart\Order::STATUS_UNSHIPPED;
                break;

            case \Ess\M2ePro\Model\Walmart\Order\Item::STATUS_SHIPPED:
                $resultStatus = \Ess\M2ePro\Model\Walmart\Order::STATUS_SHIPPED;
                break;

            case \Ess\M2ePro\Model\Walmart\Order\Item::STATUS_CANCELLED:
                $resultStatus = \Ess\M2ePro\Model\Walmart\Order::STATUS_CANCELED;
                break;
        }

        return $resultStatus;
    }

    //########################################
}

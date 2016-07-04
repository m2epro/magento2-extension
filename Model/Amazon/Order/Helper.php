<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Order;

class Helper extends \Ess\M2ePro\Model\AbstractModel
{
    const AMAZON_STATUS_PENDING             = 'Pending';
    const AMAZON_STATUS_UNSHIPPED           = 'Unshipped';
    const AMAZON_STATUS_SHIPPED_PARTIALLY   = 'PartiallyShipped';
    const AMAZON_STATUS_SHIPPED             = 'Shipped';
    const AMAZON_STATUS_UNFULFILLABLE       = 'Unfulfillable';
    const AMAZON_STATUS_CANCELED            = 'Canceled';
    const AMAZON_STATUS_INVOICE_UNCONFIRMED = 'InvoiceUnconfirmed';

    //########################################

    /**
     * @param $amazonOrderStatus
     * @return int
     */
    public function getStatus($amazonOrderStatus)
    {
        switch ($amazonOrderStatus) {
            case self::AMAZON_STATUS_UNSHIPPED:
                $status = \Ess\M2ePro\Model\Amazon\Order::STATUS_UNSHIPPED;
                break;
            case self::AMAZON_STATUS_SHIPPED_PARTIALLY:
                $status = \Ess\M2ePro\Model\Amazon\Order::STATUS_SHIPPED_PARTIALLY;
                break;
            case self::AMAZON_STATUS_SHIPPED:
                $status = \Ess\M2ePro\Model\Amazon\Order::STATUS_SHIPPED;
                break;
            case self::AMAZON_STATUS_UNFULFILLABLE:
                $status = \Ess\M2ePro\Model\Amazon\Order::STATUS_UNFULFILLABLE;
                break;
            case self::AMAZON_STATUS_CANCELED:
                $status = \Ess\M2ePro\Model\Amazon\Order::STATUS_CANCELED;
                break;
            case self::AMAZON_STATUS_INVOICE_UNCONFIRMED:
                $status = \Ess\M2ePro\Model\Amazon\Order::STATUS_INVOICE_UNCONFIRMED;
                break;
            case self::AMAZON_STATUS_PENDING:
            default:
                $status = \Ess\M2ePro\Model\Amazon\Order::STATUS_PENDING;
                break;
        }

        return $status;
    }

    //########################################
}
<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Magento\Quote;

use Ess\M2ePro\Model\Exception;

/**
 * Class FailDuringEventProcessing
 *
 * On Magento Order creating some exceptions are thrown during e.g. "sales_order_save_after" event processing, which
 * means that Magento Order was actually created
 * This exception should be thrown instead the original one with Magento Order instance inside
 */
class FailDuringEventProcessing extends Exception
{
    /** @var \Magento\Sales\Api\Data\OrderInterface|null  */
    private $order = null;

    //########################################

    public function __construct(
        \Magento\Sales\Api\Data\OrderInterface $order,
        $message = "",
        $additionalData = [],
        $code = 0,
        $sendToServer = true
    ) {
        parent::__construct($message, $additionalData, $code, $sendToServer);
        $this->order = $order;
    }

    public function getOrder()
    {
        return $this->order;
    }

    //########################################
}

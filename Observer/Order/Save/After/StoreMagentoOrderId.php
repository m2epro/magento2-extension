<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Observer\Order\Save\After;
/**
 * Class \Ess\M2ePro\Observer\Order\Save\After\StoreMagentoOrderId
 */
class StoreMagentoOrderId extends \Ess\M2ePro\Observer\AbstractModel
{
    //########################################

    public function process()
    {
        /** @var \Magento\Sales\Model\Order $magentoOrder */
        $magentoOrder = $this->getEvent()->getOrder();

        /** @var \Ess\M2ePro\Model\Order $order */
        $order = $this->getHelper('Data\GlobalData')->getValue(\Ess\M2ePro\Model\Order::ADDITIONAL_DATA_KEY_IN_ORDER);
        $this->getHelper('Data\GlobalData')->unsetValue(\Ess\M2ePro\Model\Order::ADDITIONAL_DATA_KEY_IN_ORDER);

        if (empty($order)) {
            return;
        }

        if ($order->getData('magento_order_id') == $magentoOrder->getId()) {
            return;
        }

        $order->addData([
            'magento_order_id'                           => $magentoOrder->getId(),
            'magento_order_creation_failure'             => \Ess\M2ePro\Model\Order::MAGENTO_ORDER_CREATION_FAILED_NO,
            'magento_order_creation_latest_attempt_date' => $this->getHelper('Data')->getCurrentGmtDate()
        ]);

        $order->setMagentoOrder($magentoOrder);
        $order->save();
    }

    //########################################
}

<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Observer\Ebay;

class Order extends \Ess\M2ePro\Model\Observer\AbstractModel
{
    protected $ebayFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    )
    {
        $this->ebayFactory = $ebayFactory;
        parent::__construct($helperFactory, $activeRecordFactory, $modelFactory);
    }

    //########################################

    public function process()
    {
        /** @var \Magento\Sales\Model\Order $magentoOrder */
        $magentoOrder = $this->getEvent()->getOrder();

        $origData = $magentoOrder->getOrigData();
        if (empty($origData)) {
            return;
        }

        if ($origData['status'] == $magentoOrder->getStatus() && $origData['state'] == $magentoOrder->getState()) {
            return;
        }

        try {
            /** @var \Ess\M2ePro\Model\Order $order */
            $order = $this->ebayFactory->getObjectLoaded('Order', $magentoOrder, 'magento_order_id');
        } catch (\Exception $exception) {
            return;
        }

        /** @var \Ess\M2ePro\Model\Ebay\Order $ebayOrder */
        $ebayOrder = $order->getChildObject();

        $ebayAccount = $ebayOrder->getEbayAccount();

        if (!$ebayAccount->isPickupStoreEnabled() || !$ebayAccount->isMagentoOrdersInStorePickupEnabled()) {
            return;
        }

        if ($magentoOrder->getState() == \Magento\Sales\Model\Order::STATE_CANCELED &&
            $this->sendNotification($order->getAccount(), 'cancelled', $ebayOrder->getEbayOrderId())
        ) {
            $order->addSuccessLog(
                $this->getHelper('Module\Translation')->__('Order was successfully marked as Cancelled')
            );
            return;
        }

        $readyForPickupStatus = $ebayAccount->getMagentoOrdersInStorePickupStatusReadyForPickup();
        if ($readyForPickupStatus == $magentoOrder->getStatus() &&
            $this->sendNotification($order->getAccount(), 'ready_for_pickup', $ebayOrder->getEbayOrderId())
        ) {
            $order->addSuccessLog(
                $this->getHelper('Module\Translation')->__('Order was successfully marked as Ready For Pickup')
            );
        }

        $pickedUpStatus = $ebayAccount->getMagentoOrdersInStorePickupStatusPickedUp();
        if ($pickedUpStatus == $magentoOrder->getStatus() &&
            $this->sendNotification($order->getAccount(), 'picked_up', $ebayOrder->getEbayOrderId())
        ) {
            $order->addSuccessLog(
                $this->getHelper('Module\Translation')->__('Order was successfully marked as Picked Up')
            );
        }
    }

    //########################################

    private function sendNotification($account, $orderId, $type)
    {
        $dispatcher = $this->modelFactory->getObject('Ebay\Connector\Dispatcher');
        $connector = $dispatcher->getVirtualConnector(
            'store', 'update', 'order',
            array('order_id' => $orderId, 'type' => $type),
            NULL, NULL, $account
        );

        try {
            $dispatcher->process($connector);
        } catch (\Exception $exception) {
            return false;
        }

        return true;
    }

    //########################################
}
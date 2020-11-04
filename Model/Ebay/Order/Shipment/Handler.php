<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Order\Shipment;

/**
 * Class Ess\M2ePro\Model\Ebay\Order\Shipment\Handler
 */
class Handler extends \Ess\M2ePro\Model\Order\Shipment\Handler
{
    /** @var \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory */
    protected $ebayFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Magento\Shipping\Model\CarrierFactoryInterface $carrierFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    ) {
        $this->ebayFactory = $ebayFactory;
        parent::__construct($activeRecordFactory, $carrierFactory, $helperFactory, $modelFactory);
    }

    //########################################

    protected function getComponentMode()
    {
        return \Ess\M2ePro\Helper\Component\Ebay::NICK;
    }

    //########################################

    /**
     * @param \Ess\M2ePro\Model\Order $order
     * @param \Magento\Sales\Model\Order\Shipment $shipment
     * @return int
     * @throws \Exception
     */
    public function handle(\Ess\M2ePro\Model\Order $order, \Magento\Sales\Model\Order\Shipment $shipment)
    {
        $trackingDetails = $this->getTrackingDetails($order, $shipment);
        if (!$this->isNeedToHandle($order, $trackingDetails)) {
            return self::HANDLE_RESULT_SKIPPED;
        }

        $allowedItems = [];
        $items = [];
        foreach ($shipment->getAllItems() as $shipmentItem) {
            /** @var \Magento\Sales\Model\Order\Shipment\Item $shipmentItem */
            $orderItem = $shipmentItem->getOrderItem();

            $parentOrderItemId = $orderItem->getParentItemId();
            if ($parentOrderItemId !== null && !in_array($parentOrderItemId, $allowedItems)) {
                $allowedItems[] = $parentOrderItemId;
                continue;
            }

            if (!$this->getHelper('Magento_Product')->isBundleType($orderItem->getProductType())) {
                $allowedItems[] = $orderItem->getId();
            }

            $item = $this->getItemToShipLoader($order, $shipmentItem)->loadItem();
            if (empty($item)) {
                continue;
            }

            $items += $item;
        }

        $resultItems = [];
        foreach ($items as $orderItemId => $item) {
            if (!in_array($orderItemId, $allowedItems)) {
                continue;
            }

            $resultItems[] = $item;
        }

        return $this->processStatusUpdates($order, $trackingDetails, $resultItems)
            ? self::HANDLE_RESULT_SUCCEEDED
            : self::HANDLE_RESULT_FAILED;
    }

    //########################################
}

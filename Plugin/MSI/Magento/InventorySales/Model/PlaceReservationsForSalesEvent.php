<?php
/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Plugin\MSI\Magento\InventorySales\Model;

use Magento\InventorySalesApi\Api\GetStockBySalesChannelInterface;
use Magento\InventorySalesApi\Api\Data\SalesEventInterface;
use Ess\M2ePro\Model\MSI\Order\Reserve;

/**
 * Class \Ess\M2ePro\Plugin\MSI\Magento\InventorySales\Model\PlaceReservationsForSalesEvent
 */
class PlaceReservationsForSalesEvent extends \Ess\M2ePro\Plugin\AbstractPlugin
{
    /** @var \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory */
    protected $activeRecordFactory;

    /** @var \Ess\M2ePro\Model\MSI\AffectedProducts */
    protected $msiAffectedProducts;

    /** @var \Magento\Catalog\Model\ResourceModel\Product */
    protected $productResource;

    // ---------------------------------------

    /** @var \Magento\InventorySalesApi\Api\GetStockBySalesChannelInterface */
    private $getStockByChannel;

    //########################################

    public function __construct(
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\MSI\AffectedProducts $msiAffectedProducts,
        \Magento\Catalog\Model\ResourceModel\Product $productResource,
        \Magento\Framework\ObjectManagerInterface $objectManager
    ) {
        parent::__construct($helperFactory, $modelFactory);
        $this->activeRecordFactory = $activeRecordFactory;
        $this->msiAffectedProducts = $msiAffectedProducts;
        $this->productResource = $productResource;

        $this->getStockByChannel = $objectManager->get(GetStockBySalesChannelInterface::class);
    }

    //########################################

    /**
     * @param $interceptor
     * @param \Closure $callback
     * @param array ...$arguments
     * @return mixed
     */
    public function aroundExecute($interceptor, \Closure $callback, ...$arguments)
    {
        return $this->execute('execute', $interceptor, $callback, $arguments);
    }

    /**
     * /**
     * @param $interceptor
     * @param \Closure $callback
     * @param array $arguments
     * @return mixed
     */
    public function processExecute($interceptor, \Closure $callback, array $arguments)
    {
        /** @var \Magento\InventorySalesApi\Api\Data\ItemToSellInterface[] $items */
        /** @var \Magento\InventorySalesApi\Api\Data\SalesChannelInterface $salesChannel */
        /** @var \Magento\InventorySalesApi\Api\Data\SalesEventInterface $salesEvent */

        list($items, $salesChannel, $salesEvent) = $arguments;

        $result = $callback(...$arguments);

        if ($this->isReservationCompensatingType($salesEvent)) {
            return $result;
        }

        $stock = $this->getStockByChannel->execute($salesChannel);
        foreach ($items as $item) {

            $affected = $this->msiAffectedProducts->getAffectedProductsByStockAndSku(
                $stock->getStockId(), $item->getSku()
            );

            if (empty($affected)) {
                continue;
            }

            $this->activeRecordFactory->getObject('ProductChange')->addUpdateAction(
                $this->productResource->getIdBySku($item->getSku()),
                \Ess\M2ePro\Model\ProductChange::INITIATOR_OBSERVER
            );

            foreach ($affected as $listingProduct) {
                $this->logListingProductMessage($listingProduct, $salesEvent, $salesChannel, $item);
            }
        }

        return $result;
    }

    //########################################

    private function logListingProductMessage(
        \Ess\M2ePro\Model\Listing\Product $listingProduct,
        \Magento\InventorySalesApi\Api\Data\SalesEventInterface $salesEvent,
        \Magento\InventorySalesApi\Api\Data\SalesChannelInterface $salesChannel,
        \Magento\InventorySalesApi\Api\Data\ItemToSellInterface $item
    ) {
        $log = $this->activeRecordFactory->getObject('Listing\Log');
        $log->setComponentMode($listingProduct->getComponentMode());

        $qty = abs($item->getQuantity());
        $stock = $this->getStockByChannel->execute($salesChannel);

        switch ($salesEvent->getType()) {

            case SalesEventInterface::EVENT_ORDER_PLACED:
                $resultMessage = sprintf(
                    'Product Quantity was reserved from the "%s" Stock in the amount of %s
                    because Magento Order was created.',
                    $stock->getName(),
                    $qty
                );
                break;

                case SalesEventInterface::EVENT_SHIPMENT_CREATED:
                $resultMessage = sprintf(
                    'Product Quantity reservation was released from the "%s" Stock ' .
                    'in the amount of %s because Magento Shipment was created.',
                    $stock->getName(),
                    $qty
                );
                break;

                case Reserve::EVENT_TYPE_MAGENTO_RESERVATION_PLACED:
                $resultMessage = sprintf(
                    'M2E Pro reserved Product Quantity from the "%s" Stock in the amount of %s.',
                    $stock->getName(),
                    $qty
                );
                break;

                case Reserve::EVENT_TYPE_MAGENTO_RESERVATION_RELEASED:
                $resultMessage = sprintf(
                    'M2E Pro released Product Quantity reservation from the "%s" Stock in the amount of %s.',
                    $stock->getName(),
                    $qty
                );
                break;

                default:
                if ($item->getQuantity()) {
                    $message = 'Product Quantity reservation was released ';
                } else {
                    $message = 'Product Quantity was reserved ';
                }
                $message .= 'from the "%s" Stock in the amount of %s because "%s" event occurred.';

                $resultMessage = sprintf(
                    $message,
                    $stock->getName(),
                    $qty,
                    $salesEvent->getType()
                );
        }

        $log->addProductMessage(
            $listingProduct->getListingId(),
            $listingProduct->getProductId(),
            $listingProduct->getId(),
            \Ess\M2ePro\Helper\Data::INITIATOR_EXTENSION,
            null,
            \Ess\M2ePro\Model\Listing\Log::ACTION_CHANGE_PRODUCT_QTY,
            $this->getHelper('Module\Log')->encodeDescription($resultMessage),
            \Ess\M2ePro\Model\Log\AbstractModel::TYPE_NOTICE,
            \Ess\M2ePro\Model\Log\AbstractModel::PRIORITY_LOW
        );
    }

    private function isReservationCompensatingType(
        \Magento\InventorySalesApi\Api\Data\SalesEventInterface $salesEvent
    ){
        $compensatingReservationTypes = [
            Reserve::EVENT_TYPE_COMPENSATING_RESERVATION_FBA_CREATED,
            Reserve::EVENT_TYPE_COMPENSATING_RESERVATION_FBA_SHIPPED
        ];

        return in_array($salesEvent->getType(), $compensatingReservationTypes, true);
    }

    //########################################
}

<?php
/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Observer\MSI\Product;

use Magento\Framework\App\ResourceConnection;
use Magento\InventorySalesApi\Api\GetStockBySalesChannelInterface;
use Magento\InventorySalesApi\Model\GetAssignedSalesChannelsForStockInterface;
use Magento\Store\Api\WebsiteRepositoryInterface;

/**
 * Class ReservationPlaced
 * @package Ess\M2ePro\Observer\MSI\Product
 *
 * This code is not supposed to be executed on Magento v. < 2.3.0.
 * However, classes, which are declared only on Magento v. > 2.3.0 shouldn't be requested in constructor
 * for correct "setup:di:compile" command execution on older versions.
 */
class ReservationPlaced extends \Ess\M2ePro\Observer\AbstractModel
{
    /** @var GetAssignedSalesChannelsForStockInterface */
    private $getAssignedChannels;
    /** @var WebsiteRepositoryInterface */
    private $websiteRepository;
    /** @var \Magento\Framework\ObjectManagerInterface */
    private $objectManager;
    /** @var ResourceConnection */
    private $resource;
    /** @var \Magento\Catalog\Model\ResourceModel\Product */
    private $productResource;
    /** @var \Magento\InventorySalesApi\Api\Data\SalesChannelInterface $salesChannel */
    private $salesChannel;
    /** @var \Magento\InventorySalesApi\Api\Data\SalesEventInterface $salesEvent */
    private $salesEvent;
    /** @var GetStockBySalesChannelInterface */
    private $getStockByChannel;
    /** @var \Magento\InventorySalesApi\Api\Data\ItemToSellInterface */
    private $item;
    private $affectedListingsProducts = [];
    private $productId;
    private $stock;

    //########################################

    public function __construct(\Ess\M2ePro\Helper\Factory $helperFactory,
                                \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
                                \Ess\M2ePro\Model\Factory $modelFactory,
                                \Magento\Framework\ObjectManagerInterface $objectManager,
                                \Magento\Catalog\Model\ResourceModel\Product $productResource,
                                ResourceConnection $resourceConnection,
                                WebsiteRepositoryInterface $websiteRepository)
    {
        parent::__construct($helperFactory, $activeRecordFactory, $modelFactory);
        $this->objectManager             = $objectManager;
        $this->resource                  = $resourceConnection;
        $this->productResource           = $productResource;
        $this->websiteRepository         = $websiteRepository;
        $this->getAssignedChannels       = $this->objectManager->get(GetAssignedSalesChannelsForStockInterface::class);
        $this->getStockByChannel         = $this->objectManager->get(GetStockBySalesChannelInterface::class);
    }

    //########################################

    public function process()
    {
        $this->item         = $this->getEvent()->getItem();
        $this->salesChannel = $this->getEvent()->getSalesChannel();
        $this->salesEvent   = $this->getEvent()->getSalesEvent();

        if (!$this->areThereAffectedItems()) {
            return;
        }

        $this->activeRecordFactory->getObject('ProductChange')->addUpdateAction(
            $this->getProductId(),
            \Ess\M2ePro\Model\ProductChange::INITIATOR_OBSERVER
        );

        foreach ($this->getAffectedListingsProducts() as $listingProduct) {

            /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */

            $this->logListingProductMessage(
                $listingProduct,
                \Ess\M2ePro\Model\Listing\Log::ACTION_CHANGE_PRODUCT_QTY
            );
        }
    }

    //########################################

    /**
     * @return bool
     */
    private function areThereAffectedItems()
    {
        return count($this->getAffectedListingsProducts()) > 0;
    }

    /**
     * @return array
     */
    private function getAffectedListingsProducts()
    {
        if (!empty($this->affectedListingsProducts)) {
            return $this->affectedListingsProducts;
        }

        $stockId = $this->getStock()->getStockId();

        $mergedStoreIds = [];

        $channels = $this->getAssignedChannels->execute($stockId);
        foreach ($channels as $channel) {
            $website = $this->websiteRepository->get($channel->getCode());
            $mergedStoreIds = array_merge($mergedStoreIds, $website->getStoreIds());

            if ($website->getIsDefault()) {
                $mergedStoreIds[] = 0;
            }
        }

        if (empty($mergedStoreIds)) {
            return $this->affectedListingsProducts;
        }

        return $this->affectedListingsProducts = $this->activeRecordFactory
                                                      ->getObject('Listing\Product')
                                                      ->getResource()
                                                      ->getItemsByProductId(
                                                          $this->getProductId(),
                                                          array('store_id' => $mergedStoreIds)
                                                      );
    }

    //########################################

    /**
     * @return false|int
     */
    private function getProductId()
    {
        if (!empty($this->productId)) {
            return $this->productId;
        }

        return $this->productId = $this->productResource->getIdBySku($this->item->getSku());
    }

    /**
     * @return \Magento\InventoryApi\Api\Data\StockInterface
     */
    private function getStock()
    {
        if (!empty($this->stock)) {
            return $this->stock;
        }

        return $this->stock = $this->getStockByChannel->execute($this->salesChannel);
    }

    //########################################

    /**
     * @param \Ess\M2ePro\Model\Listing\Product $listingProduct
     * @param $action
     */
    private function logListingProductMessage(\Ess\M2ePro\Model\Listing\Product $listingProduct, $action)
    {
        $log = $this->activeRecordFactory->getObject('Listing\Log');
        $log->setComponentMode($listingProduct->getComponentMode());
        $qty = abs($this->item->getQuantity());

        switch ($this->salesEvent->getType()) {
            case 'order_placed':
                $message = 'Product Quantity was reserved from the "%s" Stock ' .
                           'in the amount of %s because Magento Order was created.';
                $resultMessage = sprintf(
                    $message,
                    $this->getStock()->getName(),
                    $qty
                );
                break;
            case 'shipment_created':
                $message = 'Product Quantity reservation was released from the "%s" Stock ' .
                           'in the amount of %s because Magento Shipment was created.';
                $resultMessage = sprintf(
                    $message,
                    $this->getStock()->getName(),
                    $qty
                );
                break;
            case 'm2epro_reservation':
                $resultMessage = sprintf(
                    'M2E Pro reserved Product Quantity from the "%s" Stock in the amount of %s.',
                    $this->getStock()->getName(),
                    $qty
                );
                break;
            default:
                if ($this->item->getQuantity()) {
                    $message = 'Product Quantity reservation was released ';
                } else {
                    $message = 'Product Quantity was reserved ';
                }

                $message .= 'from the "%s" Stock in the amount of %s because "%s" event occurred.';

                $resultMessage = sprintf(
                    $message,
                    $this->getStock()->getName(),
                    $qty,
                    $this->salesEvent->getType()
                );
        }

        $log->addProductMessage(
            $listingProduct->getListingId(),
            $listingProduct->getProductId(),
            $listingProduct->getId(),
            \Ess\M2ePro\Helper\Data::INITIATOR_EXTENSION,
            NULL,
            $action,
            $this->getHelper('Module\Log')->encodeDescription($resultMessage),
            \Ess\M2ePro\Model\Log\AbstractModel::TYPE_NOTICE,
            \Ess\M2ePro\Model\Log\AbstractModel::PRIORITY_LOW
        );
    }

    //########################################
}
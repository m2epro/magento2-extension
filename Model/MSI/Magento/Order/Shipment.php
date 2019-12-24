<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\MSI\Magento\Order;

use Magento\InventorySalesApi\Model\StockByWebsiteIdResolverInterface;
use Magento\InventorySourceSelectionApi\Api\GetDefaultSourceSelectionAlgorithmCodeInterface as DefaultAlgorithm;
use Magento\InventorySourceSelectionApi\Api\SourceSelectionServiceInterface;
use Magento\Sales\Api\Data\ShipmentItemCreationInterfaceFactory;
use Magento\Sales\Api\Data\ShipmentExtensionFactory;
use Magento\InventorySourceSelectionApi\Api\Data\ItemRequestInterfaceFactory;
use Magento\InventorySourceSelectionApi\Api\Data\InventoryRequestInterfaceFactory;
use Magento\InventoryConfigurationApi\Model\IsSourceItemManagementAllowedForProductTypeInterface as isSourceManagement;

/**
 * Class \Ess\M2ePro\Model\Magento\Order\Shipment
 */
class Shipment extends \Ess\M2ePro\Model\Magento\Order\Shipment
{
    /** @var StockByWebsiteIdResolverInterface */
    private $stockByWebsiteIdResolver;

    /** @var DefaultAlgorithm */
    private $algorithm;

    /**@var SourceSelectionServiceInterface */
    private $sourceSelectionService;

    /**@var ShipmentItemCreationInterfaceFactory */
    private $itemCreationFactory;

    /** @var ShipmentExtensionFactory */
    private $shipmentExtensionFactory;

    /** @var ItemRequestInterfaceFactory */
    private $itemRequestFactory;

    /** @var InventoryRequestInterfaceFactory */
    private $inventoryRequestFactory;

    /** @var isSourceManagement */
    private $isSourceItemManagement;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\Magento\Order\Shipment\DocumentFactory $shipmentDocumentFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Magento\Framework\DB\TransactionFactory $transactionFactory,
        \Magento\Framework\ObjectManagerInterface $objectManager
    ) {
        parent::__construct($shipmentDocumentFactory, $helperFactory, $modelFactory, $transactionFactory);

        $this->itemRequestFactory       = $objectManager->get(ItemRequestInterfaceFactory::class);
        $this->inventoryRequestFactory  = $objectManager->get(InventoryRequestInterfaceFactory::class);
        $this->stockByWebsiteIdResolver = $objectManager->get(StockByWebsiteIdResolverInterface::class);
        $this->algorithm                = $objectManager->get(DefaultAlgorithm::class);
        $this->sourceSelectionService   = $objectManager->get(SourceSelectionServiceInterface::class);
        $this->itemCreationFactory      = $objectManager->get(ShipmentItemCreationInterfaceFactory::class);
        $this->shipmentExtensionFactory = $objectManager->get(ShipmentExtensionFactory::class);
        $this->isSourceItemManagement   = $objectManager->get(isSourceManagement::class);
    }

    //########################################

    protected function prepareShipments()
    {
        $selectionRequestItems   = [];
        $orderItemIdsBySku       = [];

        foreach ($this->magentoOrder->getAllItems() as $item) {
            $qtyToShip = $item->getQtyToShip();
            if ($qtyToShip == 0) {
                continue;
            }

            /**
             * Magento interface do not support situation when a bundle product
             * with the parameter "Ship Bundle Items" == "Together" is in one order with products
             * with more then 1 Source
             */
            if ($this->getHelper('Magento\Product')->isBundleType($item->getProductType()) &&
                !$item->isShipSeparately()
            ) {
                throw new \Ess\M2ePro\Model\Exception\Logic(
                    'Shipping Bundle items together is not supported by Magento in Multi Source mode.'
                );
            }

            $selectionRequestItems[] = $this->itemRequestFactory->create([
                'sku' => $item->getSku(),
                'qty' => $qtyToShip,
            ]);

            $orderItemIdsBySku[$item->getSku()] = $item->getItemId();
        }

        if (empty($selectionRequestItems) || empty($orderItemIdsBySku)) {
            return;
        }

        $websiteId = (int)$this->magentoOrder->getStore()->getWebsiteId();

        /** @var \Magento\InventorySourceSelectionApi\Api\Data\InventoryRequestInterface $inventoryRequest */
        $inventoryRequest = $this->inventoryRequestFactory->create([
            'stockId' => $this->stockByWebsiteIdResolver->execute($websiteId)->getStockId(),
            'items'   => $selectionRequestItems
        ]);

        $selectionAlgorithmCode = $this->algorithm->execute();
        $sourceSelectionResult  = $this->sourceSelectionService->execute($inventoryRequest, $selectionAlgorithmCode);

        $itemsPerSourceCode = [];

        foreach ($sourceSelectionResult->getSourceSelectionItems() as $sourceSelectionItem) {
            if ($sourceSelectionItem->getQtyToDeduct() <= 0) {
                continue;
            }
            $shipmentItem = $this->itemCreationFactory->create();
            $shipmentItem->setQty($sourceSelectionItem->getQtyToDeduct());
            $shipmentItem->setOrderItemId($orderItemIdsBySku[$sourceSelectionItem->getSku()]);
            $itemsPerSourceCode[$sourceSelectionItem->getSourceCode()][] = $shipmentItem;
        }

        /**
         * The track number of only one, last shipment is sent to Channel.
         * When creating more then one shipments for one order, problems may arise.
         */
        foreach ($itemsPerSourceCode as $sourceCode => $shipmentItems) {
            /** @var \Magento\Sales\Model\Order\Shipment $shipment */
            $shipment = $this->shipmentDocumentFactory->create($this->magentoOrder, $shipmentItems);
            $shipmentExtension = $this->shipmentExtensionFactory->create();
            $shipmentExtension->setSourceCode($sourceCode);
            $shipment->setExtensionAttributes($shipmentExtension);
            $shipment->register();

            $this->shipments[] = $shipment;
        }
    }

    //########################################
}

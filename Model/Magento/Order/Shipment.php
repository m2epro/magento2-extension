<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Magento\Order;

use Magento\InventorySalesApi\Api\Data\SalesEventInterfaceFactory;
use Magento\InventorySalesApi\Model\StockByWebsiteIdResolverInterface;
use Magento\InventoryShipping\Model\SourceDeductionRequestsFromSourceSelectionFactory as SourceDeductionRequestFactory;
use Magento\InventorySourceDeductionApi\Model\SourceDeductionServiceInterface;
use Magento\InventorySourceSelectionApi\Api\Data\InventoryRequestInterface;
use Magento\InventorySourceSelectionApi\Api\Data\InventoryRequestInterfaceFactory;
use Magento\InventorySourceSelectionApi\Api\GetDefaultSourceSelectionAlgorithmCodeInterface as Algorithm;
use Magento\InventorySourceSelectionApi\Api\SourceSelectionServiceInterface;
use Magento\InventorySourceSelectionApi\Api\Data\ItemRequestInterfaceFactory;
use Magento\Sales\Api\Data\ShipmentExtensionFactory;
use Magento\Sales\Api\Data\ShipmentItemCreationInterfaceFactory;

class Shipment extends \Ess\M2ePro\Model\AbstractModel
{
    /** @var \Magento\Framework\DB\TransactionFactory  */
    protected $transactionFactory = NULL;

    /** @var \Ess\M2ePro\Model\Magento\Order\Shipment\Factory|null  */
    protected $shipmentFactory    = NULL;

    /** @var $magentoOrder \Magento\Sales\Model\Order */
    private $magentoOrder         = NULL;

    /** @var $shipments \Magento\Sales\Model\Order\Shipment[] */
    private $shipments            = [];

    /** @var SourceDeductionServiceInterface */
    private $sourceDeductionService;

    /** @var SourceDeductionRequestFactory */
    private $sourceDeductionRequestFactory;

    /** @var ItemRequestInterfaceFactory */
    private $itemRequestFactory;

    /** @var InventoryRequestInterfaceFactory */
    private $inventoryRequestFactory;

    /** @var StockByWebsiteIdResolverInterface */
    private $stockByWebsiteIdResolver;

    /** @var Algorithm */
    private $algorithm;

    /**@var SourceSelectionServiceInterface */
    private $sourceSelectionService;

    /**@var SalesEventInterfaceFactory */
    private $salesEventFactory;

    /**@var ShipmentItemCreationInterfaceFactory */
    private $itemCreationFactory;

    /** @var ShipmentExtensionFactory */
    private $shipmentExtensionFactory;

    /** @var \Magento\Framework\ObjectManagerInterface */
    private $objectManager;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\Magento\Order\Shipment\Factory $shipmentFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Magento\Framework\DB\TransactionFactory $transactionFactory,
        \Magento\Framework\ObjectManagerInterface $objectManager
    )
    {
        $this->shipmentFactory    = $shipmentFactory;
        $this->transactionFactory = $transactionFactory;
        $this->objectManager      = $objectManager;

        parent::__construct($helperFactory, $modelFactory);
    }

    //########################################

    /**
     * @param \Magento\Sales\Model\Order $magentoOrder
     * @return $this
     */
    public function setMagentoOrder(\Magento\Sales\Model\Order $magentoOrder)
    {
        $this->magentoOrder = $magentoOrder;

        return $this;
    }

    //########################################

    public function getShipments()
    {
        return $this->shipments;
    }

    //########################################

    public function buildShipments()
    {
        $this->prepareShipments();
        foreach ($this->shipments as $shipment) {
            $this->magentoOrder->getShipmentsCollection()->addItem($shipment);
        }
    }

    //########################################

    protected function prepareShipments()
    {
        if ($this->getHelper('Magento')->isMSISupportingVersion()) {
            $this->prepareMSIShipments();
            return;
        }

        $qtys = array();
        foreach ($this->magentoOrder->getAllItems() as $item) {
            $qtyToShip = $item->getQtyToShip();

            if ($qtyToShip == 0) {
                continue;
            }

            $qtys[$item->getId()] = $qtyToShip;
        }

        // Create shipment
        // ---------------------------------------
        $shipment = $this->shipmentFactory->create($this->magentoOrder);
        $shipment->register();
        // it is necessary for updating qty_shipped field in sales_flat_order_item table
        $shipment->getOrder()->setIsInProcess(true);

        // Skip shipment observer
        // ---------------------------------------
        $this->getHelper('Data\GlobalData')->unsetValue('skip_shipment_observer');
        $this->getHelper('Data\GlobalData')->setValue('skip_shipment_observer', true);

        $this->transactionFactory
             ->create()
             ->addObject($shipment)
             ->addObject($shipment->getOrder())
             ->save();

        $this->shipments[] = $shipment;
        $this->getHelper('Data\GlobalData')->unsetValue('skip_shipment_observer');
    }

    private function prepareMSIShipments()
    {
        $this->injectDependencies();
        $selectionRequestItems = [];
        $orderItemIdsBySku     = [];

        foreach ($this->magentoOrder->getAllItems() as $item) {
            $qtyToShip = $item->getQtyToShip();

            if ($qtyToShip == 0) {
                continue;
            }

            $selectionRequestItems[] = $this->itemRequestFactory->create([
                'sku' => $item->getSku(),
                'qty' => $qtyToShip,
            ]);

            $orderItemIdsBySku[$item->getSku()] = $item->getItemId();
        }

        $websiteId = (int)$this->magentoOrder->getStore()->getWebsiteId();

        /** @var InventoryRequestInterface $inventoryRequest */
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

        $transaction = $this->transactionFactory->create();
        $shipments   = [];
        foreach ($itemsPerSourceCode as $sourceCode => $shipmentItems) {

            /** @var \Magento\Sales\Model\Order\Shipment $shipment */
            $shipment = $this->shipmentFactory->create($this->magentoOrder, $shipmentItems);
            $shipmentExtension = $this->shipmentExtensionFactory->create();
            $shipmentExtension->setSourceCode($sourceCode);
            $shipment->setExtensionAttributes($shipmentExtension);
            $shipment->register();
            // it is necessary for updating qty_shipped field in sales_flat_order_item table
            $shipment->getOrder()->setIsInProcess(true);

            $shipments[] = $shipment;

            $transaction->addObject($shipment)
                        ->addObject($shipment->getOrder());
        }

        $this->getHelper('Data\GlobalData')->unsetValue('skip_shipment_observer');
        $this->getHelper('Data\GlobalData')->setValue('skip_shipment_observer', true);

        $transaction->save();
        $this->shipments = $shipments;

        $this->getHelper('Data\GlobalData')->unsetValue('skip_shipment_observer');
    }

    private function injectDependencies()
    {
        $this->sourceDeductionService        = $this->objectManager->get(SourceDeductionServiceInterface::class);
        $this->sourceDeductionRequestFactory = $this->objectManager->get(SourceDeductionRequestFactory::class);
        $this->itemRequestFactory            = $this->objectManager->get(ItemRequestInterfaceFactory::class);
        $this->inventoryRequestFactory       = $this->objectManager->get(InventoryRequestInterfaceFactory::class);
        $this->stockByWebsiteIdResolver      = $this->objectManager->get(StockByWebsiteIdResolverInterface::class);
        $this->algorithm                     = $this->objectManager->get(Algorithm::class);
        $this->sourceSelectionService        = $this->objectManager->get(SourceSelectionServiceInterface::class);
        $this->salesEventFactory             = $this->objectManager->get(SalesEventInterfaceFactory::class);
        $this->itemCreationFactory           = $this->objectManager->get(ShipmentItemCreationInterfaceFactory::class);
        $this->shipmentExtensionFactory      = $this->objectManager->get(ShipmentExtensionFactory::class);
    }

    //########################################
}
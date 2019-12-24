<?php
/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\MSI\Order;

use Magento\InventorySalesApi\Api\Data\SalesEventInterfaceFactory;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterfaceFactory;
use Magento\InventorySalesApi\Api\Data\ItemToSellInterfaceFactory;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Api\PlaceReservationsForSalesEventInterface;

/**
 * Class \Ess\M2ePro\Model\MSI\Order\Reserve
 */
class Reserve extends \Ess\M2ePro\Model\AbstractModel
{
    const EVENT_TYPE_COMPENSATING_RESERVATION_FBA_CREATED = 'm2epro_compensating_after_fba_order_created';
    const EVENT_TYPE_COMPENSATING_RESERVATION_FBA_SHIPPED = 'm2epro_compensating_after_fba_order_shipped';

    const EVENT_TYPE_MAGENTO_RESERVATION_PLACED   = 'm2epro_reservation_placed';
    const EVENT_TYPE_MAGENTO_RESERVATION_RELEASED = 'm2epro_reservation_released';

    const M2E_ORDER_OBJECT_TYPE = 'm2epro_order';

    // ---------------------------------------

    /** @var SalesEventInterfaceFactory $salesEventFactory */
    protected $salesEventFactory;

    /** @var SalesChannelInterfaceFactory $salesChannelFactory */
    protected $salesChannelFactory;

    /** @var ItemToSellInterfaceFactory $itemsToSellFactory */
    protected $itemsToSellFactory;

    /** @var PlaceReservationsForSalesEventInterface $placeReserve */
    protected $placeReserve;

    //########################################

    /*
    * Dependencies can not be specified in constructor because MSI modules can be not installed.
    */
    public function __construct(
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        array $data = []
    ) {
        parent::__construct($helperFactory, $modelFactory, $data);

        $this->salesEventFactory   = $objectManager->get(SalesEventInterfaceFactory::class);
        $this->salesChannelFactory = $objectManager->get(SalesChannelInterfaceFactory::class);
        $this->itemsToSellFactory  = $objectManager->get(ItemToSellInterfaceFactory::class);
        $this->placeReserve        = $objectManager->get(PlaceReservationsForSalesEventInterface::class);
    }

    //########################################

    public function placeCompensationReservation(array $itemsToSell, $storeId, array $salesEventData)
    {
        $salesChannel = $this->salesChannelFactory->create([
            'data' => [
                'type' => SalesChannelInterface::TYPE_WEBSITE,
                'code' => $this->getHelper('Magento\Store')->getWebsite($storeId)->getCode()
            ]
        ]);

        $convertedItems = [];
        foreach ($itemsToSell as $itemToSell) {

            $convertedItems[] = $this->itemsToSellFactory->create([
                'sku' => $itemToSell['sku'],
                'qty' => $itemToSell['qty']
            ]);
        }

        $this->placeReserve->execute(
            $convertedItems, $salesChannel, $this->salesEventFactory->create($salesEventData)
        );
    }

    //########################################
}
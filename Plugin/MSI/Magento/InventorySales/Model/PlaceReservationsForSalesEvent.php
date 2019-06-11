<?php
/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Plugin\MSI\Magento\InventorySales\Model;

/**
 * Class PlaceReservationsForSalesEvent
 * @package Ess\M2ePro\Plugin\MSI\Magento\InventorySales\Model
 *
 * This code is not supposed to be executed on Magento v. < 2.3.0.
 * However, classes, which are declared only on Magento v. > 2.3.0 shouldn't be requested in constructor
 * for correct "setup:di:compile" command execution on older versions.
 */
class PlaceReservationsForSalesEvent extends \Ess\M2ePro\Plugin\AbstractPlugin
{
    /** @var \Magento\Framework\Event\ManagerInterface */
    private $eventManager;

    //########################################

    public function __construct(\Ess\M2ePro\Helper\Factory $helperFactory,
                                \Ess\M2ePro\Model\Factory $modelFactory,
                                \Magento\Framework\Event\ManagerInterface $eventManager) {
        parent::__construct($helperFactory, $modelFactory);
        $this->eventManager = $eventManager;
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
        if (!isset($arguments[0]) || !isset($arguments[1]) || !isset($arguments[2])) {
            return $callback(...$arguments);
        }

        /** @var \Magento\InventorySalesApi\Api\Data\ItemToSellInterface[] $items */
        /** @var \Magento\InventorySalesApi\Api\Data\SalesChannelInterface $salesChannel */
        /** @var \Magento\InventorySalesApi\Api\Data\SalesEventInterface $salesEvent */

        $items        = $arguments[0];
        $salesChannel = $arguments[1];
        $salesEvent   = $arguments[2];

        $result = $callback(...$arguments);

        foreach ($items as $itemToSell) {
            $this->eventManager->dispatch(
                'ess_product_reservation_placed',
                [
                    'item'          => $itemToSell,
                    'sales_channel' => $salesChannel,
                    'sales_event'   => $salesEvent
                ]
            );

        }

        return $result;
    }

    //########################################
}
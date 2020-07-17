<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Plugin\MSI\Magento\InventoryReservations\Model\ResourceModel;

use Magento\InventoryReservations\Model\ResourceModel\GetReservationsQuantity;

/**
 * Class \Ess\M2ePro\Plugin\MSI\Magento\InventoryReservations\Model\ResourceModel\GetReservationsQuantityCache
 */
class GetReservationsQuantityCache extends \Ess\M2ePro\Plugin\AbstractPlugin
{
    //########################################

    /** @var GetReservationsQuantity */
    private $getReservationsQuantity;

    //########################################

    public function __construct(
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Magento\Framework\ObjectManagerInterface $objectManager
    ) {
        parent::__construct($helperFactory, $modelFactory);

        $this->getReservationsQuantity = $objectManager->get(GetReservationsQuantity::class);
    }

    public function aroundExecute($interceptor, \Closure $callback, ...$arguments)
    {
        return $this->execute('execute', $interceptor, $callback, $arguments);
    }

    public function processExecute($interceptor, \Closure $callback, array $arguments)
    {
        list($sku, $stockId) = $arguments;
        $key = 'released_reservation_product_' . $sku . '_' . $stockId;
        if ($this->getHelper('Data\GlobalData')->getValue($key)) {
            return $this->getReservationsQuantity->execute($sku, $stockId);
        }

        return $callback(...$arguments);
    }

    //########################################
}
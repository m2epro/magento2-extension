<?php
/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Plugin\MSI\Magento\InventorySales\Model\ResourceModel;

use Magento\InventoryApi\Api\StockRepositoryInterface;
use Magento\InventorySalesApi\Model\GetAssignedSalesChannelsForStockInterface;

/**
 * Class ReplaceSalesChannelsDataForStock
 * @package Ess\M2ePro\Plugin\MSI\Magento\InventorySales\Model\ResourceModel
 *
 * This code is not supposed to be executed on Magento v. < 2.3.0.
 * However, classes, which are declared only on Magento v. > 2.3.0 shouldn't be requested in constructor
 * for correct "setup:di:compile" command execution on older versions.
 */
class ReplaceSalesChannelsDataForStock extends \Ess\M2ePro\Plugin\AbstractPlugin
{
    /** @var GetAssignedSalesChannelsForStockInterface */
    private $getAssignedChannels;
    /** @var \Magento\Framework\ObjectManagerInterface */
    private $objectManager;
    /** @var \Magento\Framework\Event\ManagerInterface */
    private $eventManager;
    /** @var StockRepositoryInterface */
    private $stockRepository;

    //########################################

    public function __construct(\Ess\M2ePro\Helper\Factory $helperFactory,
                                \Ess\M2ePro\Model\Factory $modelFactory,
                                \Magento\Framework\ObjectManagerInterface $objectManager,
                                \Magento\Framework\Event\ManagerInterface $eventManager) {
        parent::__construct($helperFactory, $modelFactory);
        $this->objectManager       = $objectManager;
        $this->eventManager        = $eventManager;
        $this->getAssignedChannels = $this->objectManager->get(GetAssignedSalesChannelsForStockInterface::class);
        $this->stockRepository     = $this->objectManager->get(StockRepositoryInterface::class);
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
     * @param $interceptor
     * @param \Closure $callback
     * @param array $arguments
     * @return mixed
     */
    public function processExecute($interceptor, \Closure $callback, array $arguments)
    {
        if (!isset($arguments[0]) || !isset($arguments[1])) {
            return $callback(...$arguments);
        }

        $newChannels     = $arguments[0];
        $stockId         = $arguments[1];
        $currentChannels = $this->getAssignedChannels->execute($stockId);

        $result = $callback(...$arguments);

        $addedChannels = $this->getOnlyAddedChannels($currentChannels, $newChannels);
        if (empty($addedChannels)) {
            return $result;
        }

        foreach ($addedChannels as $channel) {
            $this->eventManager->dispatch(
                'ess_stock_channel_added',
                [
                    'added_channel' => $channel,
                    'stock'         => $this->stockRepository->get($stockId)
                ]
            );

        }

        return $result;
    }

    /**
     * @param array $oldChannels
     * @param array $newChannels
     * @return array
     */
    private function getOnlyAddedChannels(array $oldChannels, array $newChannels)
    {
        $oldCodes = [];

        foreach ($oldChannels as $oldChannel) {
            $oldCodes[] = $oldChannel->getCode();
        }

        return array_filter($newChannels, function($channel) use ($oldCodes){
            return !in_array($channel->getCode(), $oldCodes, true);
        });
    }

    //########################################
}
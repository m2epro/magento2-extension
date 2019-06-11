<?php
/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Plugin\MSI\Magento\Inventory\Model\SourceItem\Command\Handler;

use Magento\Inventory\Model\ResourceModel\SourceItem\Collection;

/**
 * Class SourceItemsSaveHandler
 * @package Ess\M2ePro\Plugin\MSI\Magento\Inventory\Model\SourceItem\Command\Handler
 *
 * This code is not supposed to be executed on Magento v. < 2.3.0.
 * However, classes, which are declared only on Magento v. > 2.3.0 shouldn't be requested in constructor
 * for correct "setup:di:compile" command execution on older versions.
 */

class SourceItemsSaveHandler extends \Ess\M2ePro\Plugin\AbstractPlugin
{
    /** @var \Magento\Framework\Event\ManagerInterface */
    protected $eventManager;
    /** @var \Magento\Framework\ObjectManagerInterface */
    protected $objectManager;
    /** @var \Magento\Inventory\Model\ResourceModel\SourceItem\Collection */
    protected $sourceItemColleciton;

    //########################################

    public function __construct(\Magento\Framework\Event\ManagerInterface $eventManager,
                                \Ess\M2ePro\Helper\Factory $helperFactory,
                                \Ess\M2ePro\Model\Factory $modelFactory,
                                \Magento\Framework\ObjectManagerInterface $objectManager)
    {
        parent::__construct($helperFactory, $modelFactory);
        $this->eventManager         = $eventManager;
        $this->objectManager        = $objectManager;
        $this->sourceItemColleciton = $this->objectManager->create(Collection::class);
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
    protected function processExecute($interceptor, \Closure $callback, array $arguments)
    {
        if (!isset($arguments[0])) {
            return $callback(...$arguments);
        }

        $sourceItemPairs = [];
        foreach ($arguments[0] as $afterSourceItem) {
            /** @var \Magento\InventoryApi\Api\Data\SourceItemInterface $afterSourceItem */
            $this->sourceItemColleciton->clear()->getSelect()->reset(\Magento\Framework\DB\Select::WHERE);
            $this->sourceItemColleciton->addFieldToFilter('source_item_id', $afterSourceItem->getSourceItemId());
            $beforeSourceItem = $this->sourceItemColleciton->getFirstItem();
            if (!$beforeSourceItem->getSourceItemId()) {
                $beforeSourceItem = null;
            }
            $sourceItemPairs[] = [
                'before_item' => $beforeSourceItem,
                'after_item'  => $afterSourceItem,
                'sku'         => $afterSourceItem->getSku()
            ];
        }

        $result = $callback(...$arguments);

        foreach ($sourceItemPairs as $pair) {
            $this->eventManager->dispatch(
                'ess_sourceitem_save_after',
                [
                    'before_item' => $pair['before_item'],
                    'after_item'  => $pair['after_item'],
                    'sku'         => $pair['sku']
                ]
            );
        }

        return $result;
    }

    //########################################
}
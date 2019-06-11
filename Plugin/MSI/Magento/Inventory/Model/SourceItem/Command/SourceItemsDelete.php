<?php
/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Plugin\MSI\Magento\Inventory\Model\SourceItem\Command;

/**
 * Class SourceItemsDelete
 * @package Ess\M2ePro\Plugin\MSI\Magento\Inventory\Model\SourceItem\Command
 *
 * This code is not supposed to be executed on Magento v. < 2.3.0.
 * However, classes, which are declared only on Magento v. > 2.3.0 shouldn't be requested in constructor
 * for correct "setup:di:compile" command execution on older versions.
 */
class SourceItemsDelete extends \Ess\M2ePro\Plugin\AbstractPlugin
{
    /** @var \Magento\Framework\Event\ManagerInterface */
    protected $eventManager;

    //########################################

    public function __construct(\Magento\Framework\Event\ManagerInterface $eventManager,
                                \Ess\M2ePro\Helper\Factory $helperFactory,
                                \Ess\M2ePro\Model\Factory $modelFactory)
    {
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

        $result = $callback(...$arguments);

        foreach ($arguments[0] as $deletedSourceItem) {
            $this->eventManager->dispatch(
                'ess_sourceitem_delete_after',
                [
                    'deleted_item' => $deletedSourceItem,
                    'sku'          => $deletedSourceItem->getSku()
                ]
            );
        }

        return $result;
    }

    //########################################
}
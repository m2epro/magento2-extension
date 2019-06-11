<?php
/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Plugin\MSI\Magento\Inventory\Model\SourceItem\Command;

/**
 * Class StockSourceLinksSave
 * @package Ess\M2ePro\Plugin\MSI\Magento\Inventory\Model\SourceItem\Command
 *
 * This code is not supposed to be executed on Magento v. < 2.3.0.
 * However, classes, which are declared only on Magento v. > 2.3.0 shouldn't be requested in constructor
 * for correct "setup:di:compile" command execution on older versions.
 */
class StockSourceLinksSave extends \Ess\M2ePro\Plugin\AbstractPlugin
{
    /** @var \Magento\Framework\ObjectManagerInterface */
    private $objectManager;
    /** @var \Magento\Framework\Event\ManagerInterface */
    private $eventManager;
    /** @var \Magento\Framework\App\ResourceConnection */
    private $resource;
    /** @var \Magento\InventoryApi\Api\StockRepositoryInterface */
    private $stockRepository;

    //########################################

    public function __construct(\Ess\M2ePro\Helper\Factory $helperFactory,
                                \Ess\M2ePro\Model\Factory $modelFactory,
                                \Magento\Framework\ObjectManagerInterface $objectManager,
                                \Magento\Framework\Event\ManagerInterface $eventManager,
                                \Magento\Framework\App\ResourceConnection $resource) {
        parent::__construct($helperFactory, $modelFactory);
        $this->objectManager   = $objectManager;
        $this->eventManager    = $eventManager;
        $this->resource        = $resource;
        $this->stockRepository = $this->objectManager->get(\Magento\InventoryApi\Api\StockRepositoryInterface::class);
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
     * @param $result
     * @param array ...$arguments
     * @return mixed
     */
    public function processExecute($interceptor, \Closure $callback, array $arguments)
    {
        if (!isset($arguments[0])) {
            return $callback(...$arguments);
        }

        $stockSourceLinks = $arguments[0];
        $afterSources     = [];

        foreach ($stockSourceLinks as $link) {
            /**@var \Magento\InventoryApi\Api\Data\StockSourceLinkInterface $link */
            $afterSources[] = $link->getSourceCode();
        }
        $stockId = $link->getStockId();

        $beforeSources = $this->resource
                              ->getConnection()
                              ->select()
                              ->from($this->resource->getTableName('inventory_source_stock_link'), 'source_code')
                              ->where('stock_id = ?', $stockId)
                              ->query()
                              ->fetchAll(\PDO::FETCH_COLUMN);

        $result = $callback(...$arguments);

        sort($beforeSources) && sort($afterSources);
        if ($beforeSources === $afterSources) {
            return $result;
        }

        $this->eventManager->dispatch(
            'ess_stock_sources_changed',
            [
                'before_sources' => $beforeSources,
                'after_sources'  => $afterSources,
                'stock'          => $this->stockRepository->get($stockId)
            ]
        );

        return $result;
    }

    //########################################
}
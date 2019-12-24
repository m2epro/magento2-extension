<?php
/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Plugin\MSI\Magento\Inventory\Model\StockSourceLink\Command;

use Magento\InventoryApi\Api\Data\StockSourceLinkInterface;

/**
 * Class \Ess\M2ePro\Plugin\MSI\Magento\Inventory\Model\StockSourceLink\Command\Delete
 */
class Delete extends \Ess\M2ePro\Plugin\AbstractPlugin
{
    /** @var \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory */
    protected $activeRecordFactory;

    /** @var \Ess\M2ePro\Model\MSI\AffectedProducts */
    protected $msiAffectedProducts;

    /** @var \Magento\Framework\Api\SearchCriteriaBuilder */
    protected $searchCriteriaBuilder;

    /** @var \Ess\M2ePro\PublicServices\Product\SqlChange */
    protected $publicService;

    // ---------------------------------------

    /** @var \Magento\InventoryApi\Api\GetStockSourceLinksInterface */
    protected $getStockSourceLinks;

    /** @var \Magento\InventoryApi\Api\StockRepositoryInterface */
    protected $stockRepository;

    //########################################

    public function __construct(
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\MSI\AffectedProducts $msiAffectedProducts,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Ess\M2ePro\PublicServices\Product\SqlChange $publicService,
        \Magento\Framework\ObjectManagerInterface $objectManager
    ) {
        parent::__construct($helperFactory, $modelFactory);
        $this->activeRecordFactory = $activeRecordFactory;
        $this->msiAffectedProducts = $msiAffectedProducts;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->publicService = $publicService;

        $this->getStockSourceLinks = $objectManager->get(\Magento\InventoryApi\Api\GetStockSourceLinksInterface::class);
        $this->stockRepository = $objectManager->get(\Magento\InventoryApi\Api\StockRepositoryInterface::class);
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
        /** @var \Magento\Inventory\Model\StockSourceLink[] $stockSourceLinks */
        $stockSourceLinks = $arguments[0];
        $stockId = reset($stockSourceLinks)->getStockId();

        $afterSources = [];
        foreach ($stockSourceLinks as $link) {
            $afterSources[] = $link->getSourceCode();
        }

        $result = $callback(...$arguments);

        if (empty($afterSources)) {
            return $result;
        }

        foreach ($this->msiAffectedProducts->getAffectedListingsByStock($stockId) as $listing) {
            foreach ($listing->getChildObject()->getResource()->getUsedProductsIds($listing->getId()) as $productId) {
                $this->publicService->markQtyWasChanged($productId);
            }
            $this->logListingMessage($listing, $this->stockRepository->get($stockId));
        }
        $this->publicService->applyChanges();

        return $result;
    }

    //########################################

    private function logListingMessage(
        \Ess\M2ePro\Model\Listing $listing,
        \Magento\InventoryApi\Api\Data\StockInterface $stock
    ) {
        /** @var \Ess\M2ePro\Model\Listing\Log $log */
        $log = $this->activeRecordFactory->getObject('Listing\Log');
        $log->setComponentMode($listing->getComponentMode());

        $log->addListingMessage(
            $listing->getId(),
            \Ess\M2ePro\Helper\Data::INITIATOR_EXTENSION,
            null,
            null,
            $this->getHelper('Module\Log')->encodeDescription(
                'Source set was changed in the "%stock%" Stock used for M2E Pro Listing.',
                ['!stock'=> $stock->getName()]
            ),
            \Ess\M2ePro\Model\Log\AbstractModel::TYPE_NOTICE,
            \Ess\M2ePro\Model\Log\AbstractModel::PRIORITY_LOW
        );
    }

    //########################################
}

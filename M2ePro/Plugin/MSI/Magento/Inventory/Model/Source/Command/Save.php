<?php
/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Plugin\MSI\Magento\Inventory\Model\Source\Command;

/**
 * Class \Ess\M2ePro\Plugin\MSI\Magento\Inventory\Model\Source\Command\Save\Save
 */
class Save extends \Ess\M2ePro\Plugin\AbstractPlugin
{
    /** @var \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory */
    protected $activeRecordFactory;

    /** @var \Ess\M2ePro\Model\MSI\AffectedProducts */
    protected $msiAffectedProducts;

    /** @var \Ess\M2ePro\PublicServices\Product\SqlChange */
    protected $publicService;

    // ---------------------------------------

    /** @var \Magento\Inventory\Model\SourceRepository */
    protected $sourceRepo;

    //########################################

    /*
    * Dependencies can not be specified in constructor because MSI modules can be not installed.
    */
    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\MSI\AffectedProducts $msiAffectedProducts,
        \Ess\M2ePro\PublicServices\Product\SqlChange $publicService,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    ) {
        parent::__construct($helperFactory, $modelFactory);
        $this->activeRecordFactory = $activeRecordFactory;
        $this->msiAffectedProducts = $msiAffectedProducts;
        $this->publicService = $publicService;

        $this->sourceRepo = $objectManager->get(\Magento\Inventory\Model\SourceRepository::class);
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
        /** @var \Magento\InventoryApi\Api\Data\SourceInterface $source */
        $source = $arguments[0];

        try {
            $sourceBefore = $this->sourceRepo->get($source->getSourceCode());
        } catch (\Magento\Framework\Exception\NoSuchEntityException $noSuchEntityException) {
            return $callback(...$arguments);
        }

        $result = $callback(...$arguments);

        if ($sourceBefore->isEnabled() === $source->isEnabled()) {
            return $result;
        }

        $oldValue = $sourceBefore->isEnabled() ? 'Enabled' : 'Disabled';
        $newValue = $source->isEnabled()       ? 'Enabled' : 'Disabled';

        foreach ($this->msiAffectedProducts->getAffectedListingsBySource($source->getSourceCode()) as $listing) {
            foreach ($listing->getChildObject()->getResource()->getUsedProductsIds($listing->getId()) as $productId) {
                $this->publicService->markQtyWasChanged($productId);
            }
            $this->logListingMessage($listing, $source, $oldValue, $newValue);
        }
        $this->publicService->applyChanges();

        return $result;
    }

    //########################################

    private function logListingMessage(
        \Ess\M2ePro\Model\Listing $listing,
        \Magento\InventoryApi\Api\Data\SourceInterface $source,
        $oldValue,
        $newValue
    ) {
        $log = $this->activeRecordFactory->getObject('Listing\Log');
        $log->setComponentMode($listing->getComponentMode());

        $log->addListingMessage(
            $listing->getId(),
            \Ess\M2ePro\Helper\Data::INITIATOR_EXTENSION,
            null,
            null,
            $this->getHelper('Module\Log')->encodeDescription(
                'Status of the "%source%" Source changed [%from%] to [%to%].',
                ['!from'=> $oldValue, '!to' => $newValue, '!source' => $source->getSourceCode()]
            ),
            \Ess\M2ePro\Model\Log\AbstractModel::TYPE_NOTICE,
            \Ess\M2ePro\Model\Log\AbstractModel::PRIORITY_LOW
        );
    }

    //########################################
}

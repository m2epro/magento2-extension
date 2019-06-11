<?php
/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Observer\MSI\Stock;

/**
 * Class ChannelAdded
 * @package Ess\M2ePro\Observer\MSI\Stock
 *
 * This code is not supposed to be executed on Magento v. < 2.3.0.
 * However, classes, which are declared only on Magento v. > 2.3.0 shouldn't be requested in constructor
 * for correct "setup:di:compile" command execution on older versions.
 */
class ChannelAdded extends \Ess\M2ePro\Observer\AbstractModel
{
    /** @var \Ess\M2ePro\PublicServices\Product\SqlChange */
    protected $publicService;
    /** @var \Magento\Store\Api\WebsiteRepositoryInterface */
    protected $websiteRepository;
    /** @var \Ess\M2ePro\Model\Listing[] */
    protected $affectedListings = [];
    /** @var \Magento\InventorySalesApi\Api\Data\SalesChannelInterface */
    protected $addedChannel;
    /** @var \Magento\InventoryApi\Api\Data\StockInterface */
    protected $stock;

    //########################################

    public function __construct(\Ess\M2ePro\Helper\Factory $helperFactory,
                                \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
                                \Ess\M2ePro\Model\Factory $modelFactory,
                                \Ess\M2ePro\PublicServices\Product\SqlChange $publicService,
                                \Magento\Store\Api\WebsiteRepositoryInterface $websiteRepository)
    {
        parent::__construct($helperFactory, $activeRecordFactory, $modelFactory);
        $this->publicService     = $publicService;
        $this->websiteRepository = $websiteRepository;
    }

    //########################################

    public function process()
    {
        $this->addedChannel = $this->getEvent()->getAddedChannel();
        $this->stock        = $this->getEvent()->getStock();

        $productIds = $this->getAffectedProductIds();

        if (empty($productIds)) {
            return;
        }

        foreach ($productIds as $productId) {
            $this->publicService->markQtyWasChanged($productId);
        }

        $this->publicService->applyChanges();
        $this->writeLogs();
    }

    //########################################

    /**
     * @return array
     */
    private function getAffectedProductIds()
    {
        $listingIds = array_keys($this->getAffectedListings());

        if (empty($listingIds)) {
            return [];
        }

        $lpCollection = $this->activeRecordFactory->getObject('Listing\Product')->getCollection();
        $lpCollection->addFieldToFilter('listing_id', $listingIds);

        return $lpCollection->getColumnValues('product_id');
    }

    /**
     * @return \Ess\M2ePro\Model\Listing[]
     */
    private function getAffectedListings()
    {
        if (!empty($this->affectedListings)) {
            return $this->affectedListings;
        }

        $website  = $this->websiteRepository->get($this->addedChannel->getCode());
        $storeIds = $website->getStoreIds();

        if ($website->getIsDefault()) {
            $storeIds[] = 0;
        }

        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Collection $listingCollection */
        $listingCollection = $this->activeRecordFactory->getObject('Listing')->getCollection();
        $listingCollection->addFieldToFilter('store_id', ['in' => $storeIds]);

        return $this->affectedListings = $listingCollection->getItems();
    }

    //########################################

    private function writeLogs()
    {
        foreach ($this->getAffectedListings() as $listing) {
            /** @var \Ess\M2ePro\Model\Listing\Log $log */
            $log = $this->activeRecordFactory->getObject('Listing\Log');
            $log->setComponentMode($listing->getComponentMode());
            $log->addListingMessage(
                $listing->getId(),
                \Ess\M2ePro\Helper\Data::INITIATOR_EXTENSION,
                NULL,
                NULL,
                $this->getHelper('Module\Log')->encodeDescription(
                    sprintf(
                        'Website "%s" has been linked with stock "%s".',
                        $this->addedChannel->getCode(),
                        $this->stock->getName()
                    )
                ),
                \Ess\M2ePro\Model\Log\AbstractModel::TYPE_NOTICE,
                \Ess\M2ePro\Model\Log\AbstractModel::PRIORITY_LOW
            );
        }
    }

    //########################################
}
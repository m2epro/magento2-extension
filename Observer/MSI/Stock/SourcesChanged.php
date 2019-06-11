<?php
/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Observer\MSI\Stock;

use Magento\InventorySalesApi\Model\GetAssignedSalesChannelsForStockInterface;

/**
 * Class SourcesChanged
 * @package Ess\M2ePro\Observer\MSI\Stock
 *
 * This code is not supposed to be executed on Magento v. < 2.3.0.
 * However, classes, which are declared only on Magento v. > 2.3.0 shouldn't be requested in constructor
 * for correct "setup:di:compile" command execution on older versions.
 */
class SourcesChanged extends \Ess\M2ePro\Observer\AbstractModel
{
    /** @var \Ess\M2ePro\PublicServices\Product\SqlChange */
    private $publicService;
    /** @var \Magento\Framework\ObjectManagerInterface */
    private $objectManager;
    /** @var GetAssignedSalesChannelsForStockInterface */
    private $getAssignedChannels;
    /** @var \Magento\Store\Model\ResourceModel\Website\CollectionFactory */
    private $websiteCollectionFactory;
    /** @var \Magento\InventoryApi\Api\Data\StockInterface */
    private $stock;
    /** @var \Ess\M2ePro\Model\Listing[] */
    private $affectedListings = [];

    //########################################

    public function __construct(\Ess\M2ePro\Helper\Factory $helperFactory,
                                \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
                                \Ess\M2ePro\Model\Factory $modelFactory,
                                \Ess\M2ePro\PublicServices\Product\SqlChange $publicService,
                                \Magento\Framework\ObjectManagerInterface $objectManager,
                                \Magento\Store\Model\ResourceModel\Website\CollectionFactory $websiteCollectionFactory)
    {
        parent::__construct($helperFactory, $activeRecordFactory, $modelFactory);
        $this->publicService            = $publicService;
        $this->objectManager            = $objectManager;
        $this->websiteCollectionFactory = $websiteCollectionFactory;
        $this->getAssignedChannels      = $this->objectManager->get(GetAssignedSalesChannelsForStockInterface::class);
    }

    //########################################

    public function process()
    {
        $this->stock = $this->getEvent()->getStock();
        $productIds = $this->getAffectedProductIds();

        foreach ($productIds as $productId) {
            $this->publicService->markQtyWasChanged($productId);
        }

        $this->publicService->applyChanges();
        $this->writeLogs();
    }

    /**
     * @return array
     */
    private function getAffectedProductIds()
    {
        $listingIds = array_keys($this->getAffectedListings());

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

        $channels = $this->getAssignedChannels->execute($this->stock->getStockId());
        $channelCodes = [];
        foreach ($channels as $channel) {
            $channelCodes[] = $channel->getCode();
        }

        $websiteCollection = $this->websiteCollectionFactory->create();
        $websiteCollection->addFieldToFilter('code', ['in' => $channelCodes]);

        $storeIds = [];
        foreach ($websiteCollection->getItems() as $website) {
            $storeIds = array_merge($storeIds, $website->getStoreIds());
            if ($website->getIsDefault()) {
                $storeIds[] = 0;
            }
        }

        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Collection $lp */
        $listingCollection = $this->activeRecordFactory->getObject('Listing')->getCollection();
        $listingCollection->addFieldToFilter('store_id', ['in' => $storeIds]);

        return $this->affectedListings = $listingCollection->getItems();
    }

    private function writeLogs()
    {
        $message = 'Source set was changed in the "%s" Stock used for M2E Pro Listing.';

        foreach ($this->affectedListings as $listing) {
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
                        $message,
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
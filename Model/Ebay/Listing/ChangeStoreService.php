<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Listing;

class ChangeStoreService
{
    /** @var \Magento\Framework\App\ResourceConnection */
    private $resourceConnection;
    /** @var \Ess\M2ePro\Model\ResourceModel\Listing */
    private $listingResource;
    /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product */
    private $listingProductResource;
    /** @var \Ess\M2ePro\Model\ResourceModel\Ebay\Item */
    private $ebayItemResource;
    /** @var \Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Product */
    private $ebayListingProductResource;
    /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Instruction */
    private $instruction;
    /** @var \Ess\M2ePro\Helper\Module\Log */
    private $log;
    /** @var \Ess\M2ePro\Model\Listing\Log */
    private $logService;
    private \Magento\Store\Model\StoreManagerInterface $storeManager;

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Listing $listingResource,
        \Ess\M2ePro\Model\ResourceModel\Listing\Product $listingProductResource,
        \Ess\M2ePro\Model\ResourceModel\Ebay\Item $ebayItemResource,
        \Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Product $ebayListingProductResource,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Model\ResourceModel\Listing\Product\Instruction $instruction,
        \Ess\M2ePro\Helper\Module\Log $log,
        \Ess\M2ePro\Model\Listing\Log $logService,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->instruction = $instruction;
        $this->listingResource = $listingResource;
        $this->listingProductResource = $listingProductResource;
        $this->ebayListingProductResource = $ebayListingProductResource;
        $this->ebayItemResource = $ebayItemResource;
        $this->resourceConnection = $resourceConnection;
        $this->log = $log;
        $this->logService = $logService;
        $this->storeManager = $storeManager;
    }

    public function change(\Ess\M2ePro\Model\Listing $listing, int $storeId): void
    {
        $connection = $this->resourceConnection->getConnection();
        $connection->beginTransaction();
        $prevStoreId = $listing->getStoreId();

        try {
            $this->updateStoreViewInItem($storeId, (int)$listing->getId(), $listing->getComponentMode());
            $this->updateStoreViewInListing($listing, $storeId);

            $this->addInstruction((int)$listing->getId(), $listing->getComponentMode());
            $this->addChangeLog($listing, $prevStoreId, $storeId);
            $connection->commit();
        } catch (\Throwable $e) {
            $connection->rollBack();
            throw $e;
        }
    }

    private function updateStoreViewInItem(int $storeId, int $listingId, string $componentMode): void
    {
        $connection = $this->resourceConnection->getConnection();

        $listingTable = $this->listingResource->getMainTable();
        $listingProductTable = $this->listingProductResource->getMainTable();
        $ebayListingProductTable = $this->ebayListingProductResource->getMainTable();
        $ebayItemTable = $this->ebayItemResource->getMainTable();

        $select = $connection->select();

        $select
            ->join(
                ['elp' => $ebayListingProductTable],
                'elp.ebay_item_id = ei.id',
                ['store_id' => new \Zend_Db_Expr($storeId)]
            )
            ->join(
                ['lp' => $listingProductTable],
                'lp.id = elp.listing_product_id',
                []
            )
            ->join(
                ['l' => $listingTable],
                'l.id = lp.listing_id',
                []
            )
            ->where('l.id = ?', $listingId)
            ->where('l.component_mode = ?', $componentMode)
            ->where('lp.component_mode = ?', $componentMode)
            ->where('l.account_id = ei.account_id')
            ->where('l.marketplace_id = ei.marketplace_id');

        $query = $connection->updateFromSelect($select, ['ei' => $ebayItemTable]);
        $connection->query($query);
    }

    private function updateStoreViewInListing(\Ess\M2ePro\Model\Listing $listing, int $storeId): void
    {
        $listing->setStoreId($storeId);
        $listing->save();
    }

    private function addInstruction(int $listingId, string $componentMode): void
    {
        $connection = $this->resourceConnection->getConnection();
        $listingProductTable = $this->listingProductResource->getMainTable();
        $select = $connection->select();

        $select
            ->from($listingProductTable, 'id')
            ->where('listing_id = ?', $listingId)
            ->where('component_mode = ?', $componentMode);

        $result = $connection->fetchAll($select);
        $listingProductInstructionsData = [];

        foreach ($result as $itemId) {
            $listingProductInstructionsData[] = [
                'listing_product_id' => $itemId['id'],
                'component' => $componentMode,
                'type' => \Ess\M2ePro\Model\Listing::INSTRUCTION_TYPE_CHANGE_LISTING_STORE_VIEW,
                'initiator' => \Ess\M2ePro\Model\Listing::INSTRUCTION_INITIATOR_CHANGED_LISTING_STORE_VIEW,
                'priority' => 20,
            ];
        }
        $batches = array_chunk($listingProductInstructionsData, 10000);
        foreach ($batches as $batch) {
            $this->instruction->add($batch);
        }
    }

    private function addChangeLog(\Ess\M2ePro\Model\Listing $listing, int $prevStoreId, int $storeId): void
    {
        $this->logService->setComponentMode(\Ess\M2ePro\Helper\Component\Ebay::NICK);
        $this->logService->addListingMessage(
            $listing->getId(),
            \Ess\M2ePro\Helper\Data::INITIATOR_USER,
            null,
            \Ess\M2ePro\Model\Listing\Log::ACTION_EDIT_LISTING_SETTINGS,
            $this->log->encodeDescription(
                'The Store View for this M2E Listing was updated from ‘%from%’ to ‘%to%’.',
                [
                    '!from' => $this->getLogStoreName($prevStoreId),
                    '!to' => $this->getLogStoreName($storeId)
                ]
            ),
            \Ess\M2ePro\Model\Log\AbstractModel::TYPE_INFO
        );
    }

    private function getLogStoreName(int $storeId): string
    {
        try {
            $store = $this->storeManager->getStore($storeId);
            $result = $store->getName();
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            $result = __('Unknown Store (ID: %1)', $storeId);
        }

        return (string)$result;
    }
}

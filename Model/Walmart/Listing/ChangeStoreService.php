<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Walmart\Listing;

class ChangeStoreService
{
    /** @var \Magento\Framework\App\ResourceConnection */
    private $resourceConnection;
    /** @var \Ess\M2ePro\Model\ResourceModel\Listing */
    private $listingResource;
    /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product */
    private $listingProductResource;
    /** @var \Ess\M2ePro\Model\ResourceModel\Walmart\Item */
    private $walmartItemResource;
    /** @var \Ess\M2ePro\Model\ResourceModel\Walmart\Listing\Product */
    private $walmartListingProductResource;
    /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Instruction */
    private $instruction;

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Listing $listingResource,
        \Ess\M2ePro\Model\ResourceModel\Listing\Product $listingProductResource,
        \Ess\M2ePro\Model\ResourceModel\Walmart\Item $walmartItemResource,
        \Ess\M2ePro\Model\ResourceModel\Walmart\Listing\Product $walmartListingProductResource,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Model\ResourceModel\Listing\Product\Instruction $instruction
    ) {
        $this->instruction = $instruction;
        $this->listingResource = $listingResource;
        $this->listingProductResource = $listingProductResource;
        $this->walmartListingProductResource = $walmartListingProductResource;
        $this->walmartItemResource = $walmartItemResource;
        $this->resourceConnection = $resourceConnection;
    }

    public function change(\Ess\M2ePro\Model\Listing $listing, int $storeId): void
    {
        $connection = $this->resourceConnection->getConnection();
        $connection->beginTransaction();

        try {
            $this->updateStoreViewInItem($storeId, (int)$listing->getId(), $listing->getComponentMode());
            $this->updateStoreViewInListing($listing, $storeId);

            $this->addInstruction((int)$listing->getId(), $listing->getComponentMode());

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
        $walmartListingProductTable = $this->walmartListingProductResource->getMainTable();
        $walmartItemTable = $this->walmartItemResource->getMainTable();

        $select = $connection->select();

        $select
            ->join(
                ['wlp' => $walmartListingProductTable],
                'wlp.sku = wi.sku',
                ['store_id' => new \Zend_Db_Expr($storeId)]
            )
            ->join(
                ['lp' => $listingProductTable],
                'lp.id = wlp.listing_product_id',
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
            ->where('l.account_id = wi.account_id')
            ->where('l.marketplace_id = wi.marketplace_id');

        $query = $connection->updateFromSelect($select, ['wi' => $walmartItemTable]);
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
}

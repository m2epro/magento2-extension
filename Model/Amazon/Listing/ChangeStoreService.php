<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Amazon\Listing;

class ChangeStoreService
{
    /** @var \Magento\Framework\App\ResourceConnection */
    private $resourceConnection;
    /** @var \Ess\M2ePro\Model\ResourceModel\Listing */
    private $listingResource;
    /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product */
    private $listingProductResource;
    /** @var \Ess\M2ePro\Model\ResourceModel\Amazon\Item */
    private $amazonItemResource;
    /** @var \Ess\M2ePro\Model\ResourceModel\Amazon\Listing\Product */
    private $amazonListingProductResource;
    /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Instruction */
    private $instruction;

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Listing $listingResource,
        \Ess\M2ePro\Model\ResourceModel\Listing\Product $listingProductResource,
        \Ess\M2ePro\Model\ResourceModel\Amazon\Item $amazonItemResource,
        \Ess\M2ePro\Model\ResourceModel\Amazon\Listing\Product $amazonListingProductResource,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Model\ResourceModel\Listing\Product\Instruction $instruction
    ) {
        $this->instruction = $instruction;
        $this->listingResource = $listingResource;
        $this->listingProductResource = $listingProductResource;
        $this->amazonListingProductResource = $amazonListingProductResource;
        $this->amazonItemResource = $amazonItemResource;
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
        $amazonListingProductTable = $this->amazonListingProductResource->getMainTable();
        $amazonItemTable = $this->amazonItemResource->getMainTable();

        $select = $connection->select();

        $select
            ->join(
                ['alp' => $amazonListingProductTable],
                'alp.sku = ai.sku',
                ['store_id' => new \Zend_Db_Expr($storeId)]
            )
            ->join(
                ['lp' => $listingProductTable],
                'lp.id = alp.listing_product_id',
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
            ->where('l.account_id = ai.account_id')
            ->where('l.marketplace_id = ai.marketplace_id');

        $query = $connection->updateFromSelect($select, ['ai' => $amazonItemTable]);
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

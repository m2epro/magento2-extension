<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\ChangeTracker;

class PartManager
{
    private const PART_BATCH_SIZE = 2000;

    private \Magento\Framework\App\ResourceConnection $resourceConnection;
    private \Ess\M2ePro\Model\ResourceModel\Listing\Product $listingProductResource;

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Model\ResourceModel\Listing\Product $listingProductResource
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->listingProductResource = $listingProductResource;
    }

    /**
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getPartsCount(): int
    {
        $select = $this->resourceConnection->getConnection()->select();
        $partsCountExpression = new \Zend_Db_Expr(
            sprintf(
                'CEIL( COUNT(%s) / %s )',
                \Ess\M2ePro\Model\ResourceModel\Listing\Product::COLUMN_ID,
                self::PART_BATCH_SIZE
            )
        );
        $select->from($this->listingProductResource->getMainTable(), [$partsCountExpression]);

        return (int)$select->query()->fetchColumn();
    }

    /**
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Zend_Db_Statement_Exception
     */
    public function getListingProductParts(): \Generator
    {
        $select = $this->resourceConnection->getConnection()->select();
        $select->from($this->listingProductResource->getMainTable(), [
            'component' => \Ess\M2ePro\Model\ResourceModel\Listing\Product::COMPONENT_MODE_FIELD,
            'id' => \Ess\M2ePro\Model\ResourceModel\Listing\Product::COLUMN_ID,
        ]);
        $select->order(\Ess\M2ePro\Model\ResourceModel\Listing\Product::COLUMN_ID);
        $statement = $select->query();

        $batchCounter = 0;
        $batch = [];

        while ($row = $statement->fetch()) {
            $batchCounter++;
            $batch[$row['component']][] = (int)$row['id'];

            if ($batchCounter % self::PART_BATCH_SIZE === 0) {
                yield $this->createPart($batch);
                $batch = [];
            }
        }

        if (empty($batch)) {
            return;
        }

        yield $this->createPart($batch);
    }

    private function createPart(array $batch): array
    {
        $part = [];
        foreach ($batch as $component => $listingProductIds) {
            $part[] = new TrackerConfiguration(
                $component,
                min($listingProductIds),
                max($listingProductIds),
            );
        }

        return $part;
    }
}

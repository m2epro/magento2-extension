<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\ProductType\Validation\Modal;

class ListingProductStorage
{
    private const CHUNK_SIZE = 2000;
    private const BASE_STORAGE_KEY = '/amazon/product_type/validation/modal/listing_product_ids/part';

    /** @var \Ess\M2ePro\Model\ResourceModel\Registry */
    private $registryResource;

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Registry $registryResource
    ) {
        $this->registryResource = $registryResource;
    }

    public function setListingProductIds(array $listingProductIds): void
    {
        $this->reset();

        $currentDate = \Ess\M2ePro\Helper\Date::createCurrentGmt()->format('Y-m-d H:i:s');
        $insertData = [];
        foreach (array_chunk($listingProductIds, self::CHUNK_SIZE) as $index => $listingProductIdsChunk) {
            $insertData[] = [
                'key' => $this->makeKeyForPart(++$index),
                'value' => \Ess\M2ePro\Helper\Json::encode($listingProductIdsChunk),
                'update_date' => $currentDate,
                'create_date' => $currentDate
            ];
        }

        $this->registryResource
            ->getConnection()
            ->insertMultiple(
                $this->registryResource->getMainTable(),
                $insertData
            );
    }

    public function getListingProductIds(): array
    {
        $select = $this->registryResource->getConnection()->select();
        $select->from($this->registryResource->getMainTable(), []);
        $select->columns('value');
        $select->where($this->getKeyWhereCondition());

        $listingProductIdsParts = $this->registryResource->getConnection()->fetchCol($select);
        array_walk($listingProductIdsParts, static function (&$item) {
            $item = \Ess\M2ePro\Helper\Json::decode($item);
        });

        return array_merge(...$listingProductIdsParts);
    }

    public function reset(): void
    {
        $this->registryResource
            ->getConnection()
            ->delete(
                $this->registryResource->getMainTable(),
                $this->getKeyWhereCondition()
            );
    }

    private function makeKeyForPart(int $partNumber): string
    {
        return sprintf('%s/%s/', self::BASE_STORAGE_KEY, $partNumber);
    }

    private function getKeyWhereCondition(): string
    {
        return sprintf("`key` LIKE '%s%%'", self::BASE_STORAGE_KEY);
    }
}

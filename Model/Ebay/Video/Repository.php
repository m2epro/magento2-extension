<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Video;

class Repository
{
    private \Ess\M2ePro\Model\ResourceModel\Ebay\Video\CollectionFactory $videoCollectionFactory;

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Ebay\Video\CollectionFactory $videoCollectionFactory
    ) {
        $this->videoCollectionFactory = $videoCollectionFactory;
    }

    public function create(\Ess\M2ePro\Model\Ebay\Video $video): void
    {
        $video->save();
    }

    public function save(\Ess\M2ePro\Model\Ebay\Video $video): void
    {
        $video->save();
    }

    public function findByAccountIdAndUrl(int $accountId, string $url): ?\Ess\M2ePro\Model\Ebay\Video
    {
        $collection = $this->videoCollectionFactory->create();
        $collection->addFieldToFilter(\Ess\M2ePro\Model\ResourceModel\Ebay\Video::COLUMN_ACCOUNT_ID, $accountId);
        $collection->addFieldToFilter(\Ess\M2ePro\Model\ResourceModel\Ebay\Video::COLUMN_URL, $url);

        $video = $collection->getFirstItem();

        if ($video->isObjectNew()) {
            return null;
        }

        return $video;
    }

    /**
     * @return \Ess\M2ePro\Model\Ebay\Video[]
     */
    public function findReadyToUpload(int $status, int $accountId, int $limit): array
    {
        $collection = $this->videoCollectionFactory->create();
        $collection->addFieldToFilter(\Ess\M2ePro\Model\ResourceModel\Ebay\Video::COLUMN_STATUS, $status)
                   ->addFieldToFilter(\Ess\M2ePro\Model\ResourceModel\Ebay\Video::COLUMN_ACCOUNT_ID, $accountId)
                   ->setPageSize($limit);

        return array_values($collection->getItems());
    }

    public function removeAllByAccountId(int $accountId): void
    {
        $collection = $this->videoCollectionFactory->create();
        $collection->addFieldToFilter(\Ess\M2ePro\Model\ResourceModel\Ebay\Video::COLUMN_ACCOUNT_ID, $accountId);

        $collection->getConnection()->delete(
            $collection->getMainTable(),
            [\Ess\M2ePro\Model\ResourceModel\Ebay\Video::COLUMN_ACCOUNT_ID . ' = ?' => $accountId],
        );
    }
}

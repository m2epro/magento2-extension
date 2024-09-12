<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Amazon\Marketplace;

use Ess\M2ePro\Model\ResourceModel\Amazon\Account as AmazonAccountResource;
use Ess\M2ePro\Model\ResourceModel\Marketplace as MarketplaceResource;

class Repository
{
    private \Ess\M2ePro\Model\ResourceModel\Marketplace\CollectionFactory $collectionFactory;
    private AmazonAccountResource $amazonAccountResource;
    /**
     * @var \Ess\M2ePro\Model\ResourceModel\Marketplace
     */
    private MarketplaceResource $marketplaceResource;
    private \Ess\M2ePro\Model\MarketplaceFactory $marketplaceFactory;

    public function __construct(
        MarketplaceResource $marketplaceResource,
        \Ess\M2ePro\Model\ResourceModel\Marketplace\CollectionFactory $collectionFactory,
        AmazonAccountResource $amazonAccountResource,
        \Ess\M2ePro\Model\MarketplaceFactory $marketplaceFactory
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->amazonAccountResource = $amazonAccountResource;
        $this->marketplaceResource = $marketplaceResource;
        $this->marketplaceFactory = $marketplaceFactory;
    }

    public function get(int $marketplaceId): \Ess\M2ePro\Model\Marketplace
    {
        $marketplace = $this->find($marketplaceId);
        if ($marketplace === null) {
            throw new \RuntimeException("Marketplace '$marketplaceId' not found");
        }

        return $marketplace;
    }

    public function find(int $marketplaceId): ?\Ess\M2ePro\Model\Marketplace
    {
        $model = $this->marketplaceFactory->create();
        $this->marketplaceResource->load($model, $marketplaceId);
        if ($model->isObjectNew()) {
            return null;
        }

        if (!$model->isComponentModeAmazon()) {
            return null;
        }

        return $model;
    }

    public function findByNativeId(int $nativeId): ?\Ess\M2ePro\Model\Marketplace
    {
        $collection = $this->collectionFactory->createWithAmazonChildMode();
        $collection->addFieldToFilter(MarketplaceResource::COLUMN_NATIVE_ID, ['eq' => $nativeId]);

        $marketplace = $collection->getFirstItem();
        if ($marketplace->isObjectNew()) {
            return null;
        }

        return $marketplace;
    }

    /**
     * @return \Ess\M2ePro\Model\Marketplace[]
     */
    public function findWithAccounts(): array
    {
        $collection = $this->collectionFactory->createWithAmazonChildMode();
        $collection->joinInner(
            ['account' => $this->amazonAccountResource->getMainTable()],
            sprintf(
                'main_table.%s = account.%s',
                MarketplaceResource::COLUMN_ID,
                AmazonAccountResource::COLUMN_MARKETPLACE_ID,
            ),
            []
        );
        $collection->getSelect()->group(sprintf('main_table.%s', MarketplaceResource::COLUMN_ID));

        return array_values($collection->getItems());
    }

    /**
     * @return \Ess\M2ePro\Model\Marketplace[]
     */
    public function findActive(): array
    {
        $collection = $this->collectionFactory->createWithAmazonChildMode();
        $collection->addFieldToFilter(
            MarketplaceResource::COLUMN_STATUS,
            ['eq' => \Ess\M2ePro\Model\Marketplace::STATUS_ENABLE]
        )
                   ->setOrder(MarketplaceResource::COLUMN_SORDER, 'ASC');

        return array_values($collection->getItems());
    }
}

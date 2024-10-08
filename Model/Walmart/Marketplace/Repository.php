<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Walmart\Marketplace;

use Ess\M2ePro\Model\ResourceModel\Walmart\Account as WalmartAccountResource;
use Ess\M2ePro\Model\ResourceModel\Marketplace as MarketplaceResource;

class Repository
{
    private \Ess\M2ePro\Model\ResourceModel\Marketplace\CollectionFactory $collectionFactory;
    private \Ess\M2ePro\Model\ResourceModel\Marketplace $marketplaceResource;
    private \Ess\M2ePro\Model\MarketplaceFactory $marketplaceFactory;

    public function __construct(
        MarketplaceResource $marketplaceResource,
        \Ess\M2ePro\Model\ResourceModel\Marketplace\CollectionFactory $collectionFactory,
        WalmartAccountResource $amazonAccountResource,
        \Ess\M2ePro\Model\MarketplaceFactory $marketplaceFactory
    ) {
        $this->collectionFactory = $collectionFactory;
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

        if (!$model->isComponentModeWalmart()) {
            return null;
        }

        return $model;
    }

    /**
     * @return \Ess\M2ePro\Model\Marketplace[]
     */
    public function findActive(): array
    {
        $collection = $this->collectionFactory->createWithWalmartChildMode();
        $collection->addFieldToFilter(
            MarketplaceResource::COLUMN_STATUS,
            ['eq' => \Ess\M2ePro\Model\Marketplace::STATUS_ENABLE]
        )
                   ->setOrder(MarketplaceResource::COLUMN_SORDER, 'ASC');

        return array_values($collection->getItems());
    }
}

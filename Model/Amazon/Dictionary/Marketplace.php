<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Amazon\Dictionary;

use Ess\M2ePro\Model\ResourceModel\Amazon\Dictionary\Marketplace as ResourceModel;

class Marketplace extends \Ess\M2ePro\Model\ActiveRecord\AbstractModel
{
    public function _construct(): void
    {
        parent::_construct();
        $this->_init(\Ess\M2ePro\Model\ResourceModel\Amazon\Dictionary\Marketplace::class);
    }

    public function create(
        \Ess\M2ePro\Model\Marketplace $marketplace,
        array $productTypes
    ): self {
        $this->setData(ResourceModel::COLUMN_MARKETPLACE_ID, $marketplace->getId())
             ->setData(ResourceModel::COLUMN_PRODUCT_TYPES, json_encode($productTypes));

        return $this;
    }

    public function getId(): int
    {
        return (int)parent::getId();
    }

    public function getMarketplaceId(): int
    {
        return (int)$this->getData(ResourceModel::COLUMN_MARKETPLACE_ID);
    }

    public function getProductTypes(): array
    {
        return (array)json_decode((string)$this->getData(ResourceModel::COLUMN_PRODUCT_TYPES), true);
    }
}

<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Walmart\Dictionary;

use Ess\M2ePro\Model\ResourceModel\Walmart\Dictionary\Marketplace as ResourceModel;

class Marketplace extends \Ess\M2ePro\Model\ActiveRecord\AbstractModel
{
    public function _construct()
    {
        $this->_init(\Ess\M2ePro\Model\ResourceModel\Walmart\Dictionary\Marketplace::class);
    }

    public function init(
        int $marketplaceId,
        \DateTime $clientDetailsLastUpdateDate,
        \DateTime $serverDetailsLastUpdateDate
    ): void {
        $this->setData(ResourceModel::COLUMN_MARKETPLACE_ID, $marketplaceId);
        $this->setData(
            ResourceModel::COLUMN_CLIENT_DETAILS_LAST_UPDATE_DATE,
            $clientDetailsLastUpdateDate->format('Y-m-d H:i:s')
        );
        $this->setData(
            ResourceModel::COLUMN_SERVER_DETAILS_LAST_UPDATE_DATE,
            $serverDetailsLastUpdateDate->format('Y-m-d H:i:s')
        );
    }

    public function setProductTypes(array $productTypes): self
    {
        $this->setData(
            ResourceModel::COLUMN_PRODUCT_TYPES,
            \Ess\M2ePro\Helper\Json::encode($productTypes)
        );

        return $this;
    }

    /**
     * @return list<array{nick: string, title: string}>
     */
    public function getProductTypes(): array
    {
        $productTypes = $this->getData(ResourceModel::COLUMN_PRODUCT_TYPES);
        if (empty($productTypes)) {
            return [];
        }

        return \Ess\M2ePro\Helper\Json::decode($productTypes);
    }
}

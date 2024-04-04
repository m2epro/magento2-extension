<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Amazon\Account;

use Ess\M2ePro\Model\ResourceModel\Amazon\Account\MerchantSetting as MerchantSettingResource;

class MerchantSetting extends \Ess\M2ePro\Model\ActiveRecord\AbstractModel
{
    public function _construct(): void
    {
        parent::_construct();
        $this->_init(MerchantSettingResource::class);
    }

    public function init(string $merchantId): self
    {
        $this->setData(MerchantSettingResource::COLUMN_MERCHANT_ID, $merchantId)
             ->disableManageFbaInventorySource();

        return $this;
    }

    // ----------------------------------------

    public function getMerchantId(): string
    {
        return $this->getDataByKey(MerchantSettingResource::COLUMN_MERCHANT_ID);
    }

    // ----------------------------------------

    public function isManageFbaInventory(): bool
    {
        return (bool)$this->getDataByKey(MerchantSettingResource::COLUMN_FBA_INVENTORY_MODE);
    }

    public function enableManageFbaInventory(string $sourceName): self
    {
        $this->setManageFbaInventorySourceMode(true)
            ->setManageFbaInventorySourceName($sourceName);

        return $this;
    }

    public function disableManageFbaInventorySource(): self
    {
        $this->setManageFbaInventorySourceMode(false)
            ->clearManageFbaInventorySourceName();

        return $this;
    }

    private function setManageFbaInventorySourceMode(bool $value): self
    {
        $this->setData(MerchantSettingResource::COLUMN_FBA_INVENTORY_MODE, (int)$value);

        return $this;
    }

    private function clearManageFbaInventorySourceName(): self
    {
        $this->setData(MerchantSettingResource::COLUMN_FBA_INVENTORY_SOURCE_NAME, null);

        return $this;
    }

    private function setManageFbaInventorySourceName(string $name): self
    {
        $this->setData(MerchantSettingResource::COLUMN_FBA_INVENTORY_SOURCE_NAME, $name);

        return $this;
    }

    public function getManageFbaInventorySourceName(): string
    {
        if (!$this->isManageFbaInventory()) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Manage FBA inventory was not enabled.');
        }

        return $this->getDataByKey(MerchantSettingResource::COLUMN_FBA_INVENTORY_SOURCE_NAME);
    }
}

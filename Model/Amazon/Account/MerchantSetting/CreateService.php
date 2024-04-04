<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Amazon\Account\MerchantSetting;

class CreateService
{
    /** @var \Ess\M2ePro\Model\Amazon\Account\MerchantSetting\Repository */
    private $repository;
    /** @var \Ess\M2ePro\Model\Amazon\Account\MerchantSettingFactory */
    private $factory;

    public function __construct(
        Repository $repository,
        \Ess\M2ePro\Model\Amazon\Account\MerchantSettingFactory $factory
    ) {
        $this->repository = $repository;
        $this->factory = $factory;
    }

    public function createDefault(string $merchantId): \Ess\M2ePro\Model\Amazon\Account\MerchantSetting
    {
        $exist = $this->repository->find($merchantId);
        if ($exist !== null) {
            return $exist;
        }

        $settings = $this->factory->create()
                                  ->init($merchantId);

        $this->repository->create($settings);

        return $settings;
    }

    public function update(
        string $merchantId,
        bool $isManageFbaInventory,
        ?string $inventorySourceName
    ): \Ess\M2ePro\Model\Amazon\Account\MerchantSetting {
        $settings = $this->createDefault($merchantId);
        if ($isManageFbaInventory) {
            $settings->enableManageFbaInventory((string)$inventorySourceName);
        } else {
            $settings->disableManageFbaInventorySource();
        }

        $this->repository->save($settings);

        return $settings;
    }
}

<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Amazon\Account\MerchantSetting;

class CreateService
{
    /** @var \Ess\M2ePro\Model\Amazon\Account\MerchantSetting\Repository */
    private $repository;
    /** @var \Ess\M2ePro\Model\Amazon\Account\MerchantSettingFactory */
    private $factory;
    /** @var \Ess\M2ePro\Model\Amazon\Account\EventDispatcher */
    private $eventDispatcher;

    public function __construct(
        Repository $repository,
        \Ess\M2ePro\Model\Amazon\Account\MerchantSettingFactory $factory,
        \Ess\M2ePro\Model\Amazon\Account\EventDispatcher $eventDispatcher
    ) {
        $this->repository = $repository;
        $this->factory = $factory;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function createDefault(
        \Ess\M2ePro\Model\Amazon\Account $amazonAccount
    ): \Ess\M2ePro\Model\Amazon\Account\MerchantSetting {
        $exist = $this->repository->find($amazonAccount->getMerchantId());
        if ($exist !== null) {
            return $exist;
        }

        $settings = $this->factory->create()
                                  ->init($amazonAccount->getMerchantId());

        $this->repository->create($settings);

        $this->eventDispatcher->dispatchEventCreatedSettingManageFbaInventory(
            $settings->isManageFbaInventory(),
            $amazonAccount
        );

        return $settings;
    }

    public function update(
        \Ess\M2ePro\Model\Amazon\Account $amazonAccount,
        bool $isManageFbaInventory,
        string $inventorySourceName = null
    ): \Ess\M2ePro\Model\Amazon\Account\MerchantSetting {
        $settings = $this->createDefault($amazonAccount);

        if ($isManageFbaInventory && $inventorySourceName !== null) {
            $settings->enableManageFbaInventory($inventorySourceName);
        } else {
            $settings->disableManageFbaInventorySource();
        }

        $this->repository->save($settings);

        $this->eventDispatcher->dispatchEventUpdatedSettingManageFbaInventory(
            $settings->isManageFbaInventory(),
            $amazonAccount
        );

        return $settings;
    }
}

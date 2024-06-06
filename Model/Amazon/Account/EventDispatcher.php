<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Amazon\Account;

class EventDispatcher
{
    /** @var \Magento\Framework\Event\ManagerInterface */
    private $eventManager;

    public function __construct(\Magento\Framework\Event\ManagerInterface $eventManager)
    {
        $this->eventManager = $eventManager;
    }

    // ----------------------------------------

    public function dispatchEventCreatedSettingManageFbaInventory(
        bool $isEnabledManageFbaInventory,
        \Ess\M2ePro\Model\Amazon\Account $amazonAccount
    ): void {
        $marketplace = $amazonAccount->getMarketplace();

        $this->eventManager->dispatch(
            'ess_amazon_account_created_setting_manage_fba_inventory',
            [
                'is_enabled_manage_fba_inventory' => $isEnabledManageFbaInventory,
                'merchant_id' => $amazonAccount->getMerchantId(),
                'is_american_region' => $marketplace->isAmericanRegion(),
                'is_european_region' => $marketplace->isEuropeanRegion(),
                'is_asian_pacific_region' => $marketplace->isAsianPacificRegion(),
            ]
        );
    }

    public function dispatchEventUpdatedSettingManageFbaInventory(
        bool $isEnabledManageFbaInventory,
        \Ess\M2ePro\Model\Amazon\Account $amazonAccount
    ): void {
        $marketplace = $amazonAccount->getMarketplace();

        $this->eventManager->dispatch(
            'ess_amazon_account_updated_setting_manage_fba_inventory',
            [
                'is_enabled_manage_fba_inventory' => $isEnabledManageFbaInventory,
                'merchant_id' => $amazonAccount->getMerchantId(),
                'is_american_region' => $marketplace->isAmericanRegion(),
                'is_european_region' => $marketplace->isEuropeanRegion(),
                'is_asian_pacific_region' => $marketplace->isAsianPacificRegion(),
            ]
        );
    }

    // ----------------------------------------

    public function dispatchEventAccountDeleted(string $merchantId): void
    {
        $this->eventManager->dispatch(
            'ess_amazon_account_deleted',
            ['merchant_id' => $merchantId]
        );
    }
}

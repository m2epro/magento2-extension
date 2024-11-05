<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Amazon\Account;

class EventDispatcher
{
    private const REGION_AMERICA = 'america';
    private const REGION_EUROPE = 'europe';
    private const REGION_ASIA_PACIFIC = 'asia-pacific';

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
                'region' => $this->resolveRegion($marketplace),

                // Deprecated. Region flags are needed to ensure backward compatibility
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
                'region' => $this->resolveRegion($marketplace),

                // Deprecated. Region flags are necessary to maintain backward compatibility
                'is_american_region' => $marketplace->isAmericanRegion(),
                'is_european_region' => $marketplace->isEuropeanRegion(),
                'is_asian_pacific_region' => $marketplace->isAsianPacificRegion(),
            ]
        );
    }

    private function resolveRegion(\Ess\M2ePro\Model\Marketplace $marketplace): string
    {
        if ($marketplace->isAmericanRegion()) {
            return self::REGION_AMERICA;
        }

        if ($marketplace->isEuropeanRegion()) {
            return self::REGION_EUROPE;
        }

        return self::REGION_ASIA_PACIFIC;
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

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
    private \Ess\M2ePro\Model\Amazon\Account\Repository $accountRepository;

    public function __construct(
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Ess\M2ePro\Model\Amazon\Account\Repository $accountRepository
    ) {
        $this->eventManager = $eventManager;
        $this->accountRepository = $accountRepository;
    }

    // ----------------------------------------

    public function dispatchEventCreatedSettingManageFbaInventory(
        \Ess\M2ePro\Model\Amazon\Account $amazonAccount
    ): void {
        $marketplace = $amazonAccount->getMarketplace();

        $this->eventManager->dispatch(
            'ess_amazon_account_created_setting_manage_fba_inventory',
            [
                'is_enabled_manage_fba_inventory' => $this->isEnabledFbaInventoryMode($amazonAccount),
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
        \Ess\M2ePro\Model\Amazon\Account $amazonAccount
    ): void {
        $marketplace = $amazonAccount->getMarketplace();

        $this->eventManager->dispatch(
            'ess_amazon_account_updated_setting_manage_fba_inventory',
            [
                'is_enabled_manage_fba_inventory' => $this->isEnabledFbaInventoryMode($amazonAccount),
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

    private function isEnabledFbaInventoryMode(\Ess\M2ePro\Model\Amazon\Account $amazonAccount): bool
    {
        return $amazonAccount->isEnabledFbaInventoryMode() ||
            $this->accountRepository->findWithEnabledFbaInventoryByMerchantId($amazonAccount->getMerchantId()) !== null;
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

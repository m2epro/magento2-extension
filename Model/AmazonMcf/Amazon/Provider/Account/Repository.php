<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\AmazonMcf\Amazon\Provider\Account;

class Repository
{
    /** @var \Ess\M2ePro\Model\Amazon\Account\Repository $amazonAccountRepository */
    private $amazonAccountRepository;
    /** @var bool */
    private $isLoaded = false;
    /** @var array<string, \M2E\AmazonMcf\Model\Provider\Amazon\Account\Item> */
    private $itemsByMerchantId = [];

    public function __construct(\Ess\M2ePro\Model\Amazon\Account\Repository $amazonAccountRepository)
    {
        $this->amazonAccountRepository = $amazonAccountRepository;
    }

    public function isExists(string $merchantId): bool
    {
        return $this->find($merchantId) !== null;
    }

    /**
     * @return \M2E\AmazonMcf\Model\Provider\Amazon\Account\Item|null
     */
    public function find(string $merchantId)
    {
        $this->load();

        return $this->itemsByMerchantId[$merchantId] ?? null;
    }

    /**
     * @return \M2E\AmazonMcf\Model\Provider\Amazon\Account\Item[]
     */
    public function getAll(): array
    {
        $this->load();

        return array_values($this->itemsByMerchantId);
    }

    private function load(): void
    {
        if ($this->isLoaded) {
            return;
        }

        $accountsByMerchantId = $this->amazonAccountRepository->getAllGroupedByMerchantId();
        if (empty($accountsByMerchantId)) {
            $this->isLoaded = true;

            return;
        }

        $this->itemsByMerchantId = [];

        foreach ($accountsByMerchantId as $merchantId => $accounts) {
            /** @var \Ess\M2ePro\Model\Amazon\Account $account */
            $account = reset($accounts);
            $isEnabled = $this->isMerchantHasEnabledFbaInventory($accounts);
            $this->itemsByMerchantId[$merchantId] = $this->createItem(
                $merchantId,
                $isEnabled,
                $account->getMarketplace()
            );
        }

        $this->isLoaded = true;
    }

    /**
     * @return \M2E\AmazonMcf\Model\Provider\Amazon\Account\Item
     */
    private function createItem(string $merchantId, bool $isEnabled, \Ess\M2ePro\Model\Marketplace $marketplace)
    {
        if ($marketplace->isAmericanRegion()) {
            return \M2E\AmazonMcf\Model\Provider\Amazon\Account\Item::createForAmericaRegion(
                $merchantId,
                $isEnabled
            );
        }

        if ($marketplace->isEuropeanRegion()) {
            return \M2E\AmazonMcf\Model\Provider\Amazon\Account\Item::createForEuropeRegion(
                $merchantId,
                $isEnabled
            );
        }

        if ($marketplace->isAsianPacificRegion()) {
            return \M2E\AmazonMcf\Model\Provider\Amazon\Account\Item::createForAsiaPacificRegion(
                $merchantId,
                $isEnabled
            );
        }

        throw new \LogicException('Unknown Region');
    }

    private function isMerchantHasEnabledFbaInventory(array $accounts): bool
    {
        foreach ($accounts as $account) {
            if ($account->isEnabledFbaInventoryMode()) {
                return true;
            }
        }

        return false;
    }
}

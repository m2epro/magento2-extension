<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\AmazonMcf\Amazon\Provider\Account;

use M2E\AmazonMcf\Model\Provider\Amazon\Account\Item;

class Repository
{
    /** @var \Ess\M2ePro\Model\Amazon\Account\Repository $amazonAccountRepository */
    private $amazonAccountRepository;
    /** @var bool */
    private $isLoaded = false;
    /** @var array<string, Item> */
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
     * @return Item[]
     */
    public function get(string $merchantId)
    {
        $item = $this->find($merchantId);
        if ($item === null) {
            throw new \LogicException(
                sprintf('Item with Merchant ID %s not found.', $merchantId)
            );
        }

        return $item;
    }

    /**
     * @return Item[]|null
     */
    public function find(string $merchantId): ?Item
    {
        $this->load();

        return $this->itemsByMerchantId[$merchantId] ?? null;
    }

    /**
     * @return Item[]
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
            $isEnabled = $account->getMerchantSetting()->isManageFbaInventory();
            $this->itemsByMerchantId[$merchantId] = $this->createItem(
                $merchantId,
                $isEnabled,
                $account->getMarketplace()
            );
        }

        $this->isLoaded = true;
    }

    private function createItem(string $merchantId, bool $isEnabled, \Ess\M2ePro\Model\Marketplace $marketplace): Item
    {
        if ($marketplace->isAmericanRegion()) {
            return Item::createForAmericaRegion($merchantId, $isEnabled);
        }

        if ($marketplace->isEuropeanRegion()) {
            return Item::createForEuropeRegion($merchantId, $isEnabled);
        }

        if ($marketplace->isAsianPacificRegion()) {
            return Item::createForAsiaPacificRegion($merchantId, $isEnabled);
        }

        throw new \LogicException('Unknown Region');
    }
}

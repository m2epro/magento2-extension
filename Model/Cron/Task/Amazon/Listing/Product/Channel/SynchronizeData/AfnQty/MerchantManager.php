<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task\Amazon\Listing\Product\Channel\SynchronizeData\AfnQty;

use Ess\M2ePro\Helper\Date as DateHelper;

class MerchantManager
{
    private const REGISTRY_PREFIX = '/amazon/inventory/afn_qty/by_merchant/';
    private const REGISTRY_SUFFIX = '/last_update/';

    /** @var bool */
    private $init = false;
    /** @var array */
    private $merchantAccounts = [];
    /** @var array */
    private $accountIdToMerchantId = [];
    /** @var \Ess\M2ePro\Model\Registry\Manager */
    private $registryManager;
    /** @var \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory */
    private $parentFactory;

    /**
     * @param \Ess\M2ePro\Model\Registry\Manager $registryManager
     * @param \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory
     */
    public function __construct(
        \Ess\M2ePro\Model\Registry\Manager $registryManager,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory
    ) {
        $this->registryManager = $registryManager;
        $this->parentFactory = $parentFactory;
    }

    /**
     * @return void
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function init(): void
    {
        if ($this->init) {
            return;
        }

        /** @var \Ess\M2ePro\Model\ResourceModel\Account\Collection $accountsCollection */
        $accountsCollection = $this->parentFactory->getObject(
            \Ess\M2ePro\Helper\Component\Amazon::NICK,
            'Account'
        )->getCollection();

        /** @var \Ess\M2ePro\Model\Account $item */
        foreach ($accountsCollection->getItems() as $item) {
            $merchantId = $item->getChildObject()->getData('merchant_id');
            $this->merchantAccounts[$merchantId][] = $item;

            $this->accountIdToMerchantId[(int)$item->getId()] = $merchantId;
        }

        $this->init = true;
    }

    /**
     * @return array
     */
    public function getMerchantsIds(): array
    {
        return array_keys($this->merchantAccounts);
    }

    /**
     * @param string $merchantId
     *
     * @return array
     */
    public function getMerchantAccountsIds(string $merchantId): array
    {
        if (empty($this->merchantAccounts[$merchantId])) {
            return [];
        }

        $accountsIds = [];
        /** @var \Ess\M2ePro\Model\Account $account */
        foreach ($this->merchantAccounts[$merchantId] as $account) {
            $accountsIds[] = $account->getId();
        }

        return $accountsIds;
    }

    /**
     * @param string $merchantId
     *
     * @return \Ess\M2ePro\Model\Account[]
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getMerchantAccounts(string $merchantId): array
    {
        if (empty($this->merchantAccounts[$merchantId])) {
            throw new \Ess\M2ePro\Model\Exception\Logic(
                'Incorrect MerchantManager usage: you need to do init() first!'
            );
        }

        return $this->merchantAccounts[$merchantId];
    }

    /**
     * @param string $merchantId
     *
     * @return \Ess\M2ePro\Model\Account
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getMerchantAccount(string $merchantId): \Ess\M2ePro\Model\Account
    {
        if (empty($this->merchantAccounts[$merchantId][0])) {
            throw new \Ess\M2ePro\Model\Exception\Logic(
                'Incorrect MerchantManager usage: you need to do init() first!'
            );
        }

        return $this->merchantAccounts[$merchantId][0];
    }

    /**
     * @param int $accountId
     *
     * @return string|null
     */
    public function getMerchantIdByAccountId(int $accountId): ?string
    {
        return $this->accountIdToMerchantId[$accountId] ?? null;
    }

    /**
     * @param string $merchantId
     * @param int $interval
     *
     * @return bool
     * @throws \Exception
     */
    public function isIntervalExceeded(string $merchantId, int $interval): bool
    {
        $lastUpdate = $this->registryManager->getValue(self::REGISTRY_PREFIX . $merchantId . self::REGISTRY_SUFFIX);
        if (!$lastUpdate) {
            return true;
        }

        $now = DateHelper::createCurrentGmt();
        $lastUpdateDate = DateHelper::createDateGmt($lastUpdate);

        return $now->getTimestamp() - $lastUpdateDate->getTimestamp() > $interval;
    }

    /**
     * @param string $merchantId
     * @param string|null $value
     *
     * @return void
     * @throws \Exception
     */
    private function setMerchantLastUpdate(string $merchantId, ?string $value): void
    {
        $this->registryManager->setValue(
            self::REGISTRY_PREFIX . $merchantId . self::REGISTRY_SUFFIX,
            $value
        );
    }

    /**
     * @param string $merchantId
     *
     * @return void
     * @throws \Exception
     */
    public function setMerchantLastUpdateNow(string $merchantId): void
    {
        $now = DateHelper::createCurrentGmt();
        $this->setMerchantLastUpdate(
            $merchantId,
            $now->format('Y-m-d H:i:s')
        );
    }

    /**
     * @param string $merchantId
     *
     * @return void
     * @throws \Exception
     */
    public function resetMerchantLastUpdate(string $merchantId): void
    {
        $this->setMerchantLastUpdate($merchantId, null);
    }
}

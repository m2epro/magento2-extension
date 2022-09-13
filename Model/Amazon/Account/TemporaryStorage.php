<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Account;

class TemporaryStorage
{
    /** @var string[] */
    private $deleteKeys = [
        'account_id',
        'account_title',
        'marketplace_id',
        'merchant',
        'mws_token',
    ];
    /** @var \Ess\M2ePro\Model\Registry\Manager */
    private $registry;

    public function __construct(
        \Ess\M2ePro\Model\Registry\Manager $registry
    ) {
        $this->registry = $registry;
    }

    // ----------------------------------------

    /**
     * @param int $accountId
     *
     * @return void
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function setAccountId(int $accountId): void
    {
        $this->setValue('account_id', $accountId);
    }

    /**
     * @return array|string|null
     */
    public function getAccountId()
    {
        return $this->getValue('account_id');
    }

    /**
     * @param $accountTitle
     *
     * @return void
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function setAccountTitle($accountTitle): void
    {
        $this->setValue('account_title', $accountTitle);
    }

    /**
     * @return array|string|null
     */
    public function getAccountTitle()
    {
        return $this->getValue('account_title');
    }

    /**
     * @param $marketplaceId
     *
     * @return void
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function setMarketplaceId($marketplaceId): void
    {
        $this->setValue('marketplace_id', $marketplaceId);
    }

    /**
     * @return array|string|null
     */
    public function getMarketplaceId()
    {
        return $this->getValue('marketplace_id');
    }

    /**
     * @param $merchant
     *
     * @return void
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function setMerchant($merchant): void
    {
        $this->setValue('merchant', $merchant);
    }

    /**
     * @return array|string|null
     */
    public function getMerchant()
    {
        return $this->getValue('merchant');
    }

    /**
     * @param $token
     *
     * @return void
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function setMWSToken($token): void
    {
        $this->setValue('mws_token', $token);
    }

    /**
     * @return array|string|null
     */
    public function getMWSToken()
    {
        return $this->getValue('mws_token');
    }

    // ----------------------------------------

    /**
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function removeAllValues(): void
    {
        foreach ($this->deleteKeys as $key) {
            $this->registry->deleteValue($this->makeRegistryKey($key));
        }
    }

    // ----------------------------------------

    /**
     * @param string $key
     * @param mixed $value
     *
     * @return void
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    private function setValue(string $key, $value): void
    {
        $this->registry->setValue($this->makeRegistryKey($key), $value);
    }

    /**
     * @param string $key
     *
     * @return array|string|null
     */
    private function getValue(string $key)
    {
        return $this->registry->getValue($this->makeRegistryKey($key));
    }

    /**
     * @param string $key
     *
     * @return string
     */
    private function makeRegistryKey(string $key): string
    {
        return sprintf('/amazon/account/temporary_storage/%s/', $key);
    }
}

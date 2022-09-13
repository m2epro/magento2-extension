<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Account;

class TemporaryStorage
{
    /** @var string[] */
    private $deleteKeys = [
        'account_id',
        'account_title',
        'account_mode',
        'session_id',
        'sell_api_token',
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
     * @param $accountId
     *
     * @return void
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function setAccountId($accountId): void
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
     * @param $accountMode
     *
     * @return void
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function setAccountMode($accountMode): void
    {
        $this->setValue('account_mode', $accountMode);
    }

    /**
     * @return array|string|null
     */
    public function getAccountMode()
    {
        return $this->getValue('account_mode');
    }

    /**
     * @param $sessionId
     *
     * @return void
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function setSessionId($sessionId): void
    {
        $this->setValue('session_id', $sessionId);
    }

    /**
     * @return array|string|null
     */
    public function getSessionId()
    {
        return $this->getValue('session_id');
    }

    // ----------------------------------------

    /**
     * @param $sellApiToken
     *
     * @return void
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function setSellApiToken($sellApiToken): void
    {
        $this->setValue('sell_api_token', $sellApiToken);
    }

    /**
     * @return array|string|null
     */
    public function getSellApiToken()
    {
        return $this->getValue('sell_api_token');
    }

    // ----------------------------------------

    /**
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteAllValues(): void
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
        return sprintf('/ebay/account/temporary_storage/%s/', $key);
    }
}

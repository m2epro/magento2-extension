<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Walmart\Account;

class AuthCodeSessionStorage
{
    private \Ess\M2ePro\Helper\Data\Session $sessionHelper;
    private \Ess\M2ePro\Helper\Data $dataHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Data\Session $sessionHelper,
        \Ess\M2ePro\Helper\Data $dataHelper
    ) {
        $this->sessionHelper = $sessionHelper;
        $this->dataHelper = $dataHelper;
    }

    public function setAccountId(string $authCode, int $accountId): void
    {
        $this->sessionHelper->setValue($this->getKey($authCode), $accountId);
    }

    public function getAccountId(string $authCode): ?int
    {
        return $this->sessionHelper->getValue($this->getKey($authCode));
    }

    private function getKey(string $authCode): string
    {
        return $this->dataHelper->md5String($authCode);
    }
}

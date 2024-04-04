<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Amazon\Account;

class MerchantSettingFactory
{
    /** @var \Magento\Framework\ObjectManagerInterface */
    private $objectManager;

    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function create(): MerchantSetting
    {
        return $this->objectManager->create(MerchantSetting::class);
    }
}

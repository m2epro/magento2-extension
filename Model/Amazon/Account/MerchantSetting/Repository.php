<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Amazon\Account\MerchantSetting;

use Ess\M2ePro\Model\ResourceModel\Amazon\Account\MerchantSetting as MerchantSettingResource;

class Repository
{
    /** @var MerchantSettingResource */
    private $merchantSettingResource;
    /** @var \Ess\M2ePro\Model\Amazon\Account\MerchantSettingFactory */
    private $merchantSettingFactory;
    /** @var \Ess\M2ePro\Model\ResourceModel\Amazon\Account\MerchantSetting\CollectionFactory */
    private $collectionFactory;

    public function __construct(
        MerchantSettingResource $merchantSettingResource,
        \Ess\M2ePro\Model\Amazon\Account\MerchantSettingFactory $merchantSettingFactory,
        MerchantSettingResource\CollectionFactory $collectionFactory
    ) {
        $this->merchantSettingResource = $merchantSettingResource;
        $this->merchantSettingFactory = $merchantSettingFactory;
        $this->collectionFactory = $collectionFactory;
    }

    public function create(\Ess\M2ePro\Model\Amazon\Account\MerchantSetting $merchantSetting): void
    {
        $merchantSetting->isObjectNew(true);
        $merchantSetting->save();
    }

    public function save(\Ess\M2ePro\Model\Amazon\Account\MerchantSetting $merchantSetting): void
    {
        $merchantSetting->isObjectNew(false);
        $merchantSetting->save();
    }

    public function delete(\Ess\M2ePro\Model\Amazon\Account\MerchantSetting $merchantSetting): void
    {
        $this->merchantSettingResource->delete($merchantSetting);
    }

    public function get(string $merchantId): \Ess\M2ePro\Model\Amazon\Account\MerchantSetting
    {
        $settings = $this->find($merchantId);
        if ($settings === null) {
            throw new \Ess\M2ePro\Model\Exception\Logic(sprintf('Settings for merchant %s not found', $merchantId));
        }

        return $settings;
    }

    public function find(string $merchantId): ?\Ess\M2ePro\Model\Amazon\Account\MerchantSetting
    {
        $merchantSetting = $this->merchantSettingFactory->create();
        $this->merchantSettingResource->load(
            $merchantSetting,
            $merchantId,
            MerchantSettingResource::COLUMN_MERCHANT_ID
        );

        if ($merchantSetting->isObjectNew()) {
            return null;
        }

        return $merchantSetting;
    }

    /**
     * @return \Ess\M2ePro\Model\Amazon\Account\MerchantSetting[]
     */
    public function getAll(): array
    {
        $collection = $this->collectionFactory->create();

        return array_values($collection->getItems());
    }
}

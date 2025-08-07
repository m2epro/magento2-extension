<?php

declare(strict_types=1);

namespace Ess\M2ePro\Setup\Update\y25_m07;

class DecreaseAmazonProcessingTime extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    public function execute(): void
    {
        $configs = [
            [
                'group' => '/amazon/listing/product/action/revise_qty/',
                'key' => 'min_allowed_wait_interval',
                'value' => '300',
            ],
            [
                'group' => '/amazon/listing/product/action/revise_price/',
                'key' => 'min_allowed_wait_interval',
                'value' => '300',
            ],
            [
                'group' => '/amazon/listing/product/action/revise_details/',
                'key' => 'min_allowed_wait_interval',
                'value' => '300',
            ],
        ];

        foreach ($configs as $configData) {
            $this->updateConfigValue($configData['group'], $configData['key'], $configData['value']);
        }
    }

    private function updateConfigValue(string $group, string $key, string $value)
    {
        $this
            ->getConfigModifier()
            ->getEntity($group, $key)
            ->updateValue($value);
    }
}

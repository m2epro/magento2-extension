<?php

declare(strict_types=1);

namespace Ess\M2ePro\Setup\Upgrade\v1_79_1__v1_79_2;

class Config extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractConfig
{
    public function getFeaturesList(): array
    {
        return [
            '@y25_m05/DeleteEbayUnmanagedDuplicatesByListingProducts',
        ];
    }
}

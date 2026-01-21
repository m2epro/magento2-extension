<?php

declare(strict_types=1);

namespace Ess\M2ePro\Setup\Upgrade\v1_87_3__v1_88_0;

class Config extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractConfig
{
    public function getFeaturesList(): array
    {
        return [
            '@y26_m01/EbayAddIsFullRefundToOrder',
            '@y26_m01/EbayAddImportChannelInfo',
        ];
    }
}

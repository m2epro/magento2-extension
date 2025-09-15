<?php

declare(strict_types=1);

namespace Ess\M2ePro\Setup\Upgrade\v1_84_0__v1_85_0;

class Config extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractConfig
{
    public function getFeaturesList(): array
    {
        return [
            '@y25_m09/EnableAmazonBusinessForAustralia',
        ];
    }
}

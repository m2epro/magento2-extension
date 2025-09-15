<?php

declare(strict_types=1);

namespace Ess\M2ePro\Setup\Update\y25_m09;

use Ess\M2ePro\Helper\Module\Database\Tables as TablesHelper;

class EnableAmazonBusinessForAustralia extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    private const B2B_MARKETPLACES = [
        \Ess\M2ePro\Helper\Component\Amazon::MARKETPLACE_US,
        \Ess\M2ePro\Helper\Component\Amazon::MARKETPLACE_CA,
        \Ess\M2ePro\Helper\Component\Amazon::MARKETPLACE_MX,
        \Ess\M2ePro\Helper\Component\Amazon::MARKETPLACE_UK,
        \Ess\M2ePro\Helper\Component\Amazon::MARKETPLACE_FR,
        \Ess\M2ePro\Helper\Component\Amazon::MARKETPLACE_DE,
        \Ess\M2ePro\Helper\Component\Amazon::MARKETPLACE_IT,
        \Ess\M2ePro\Helper\Component\Amazon::MARKETPLACE_ES,
        \Ess\M2ePro\Helper\Component\Amazon::MARKETPLACE_AU,
        \Ess\M2ePro\Helper\Component\Amazon::MARKETPLACE_JP,
        \Ess\M2ePro\Helper\Component\Amazon::MARKETPLACE_IN,
    ];

    public function execute(): void
    {
        $amazonMarketplaceTableName = $this->getFullTableName(TablesHelper::TABLE_AMAZON_MARKETPLACE);

        $this->getConnection()->update(
            $amazonMarketplaceTableName,
            ['is_business_available' => 0]
        );

        $this->getConnection()->update(
            $amazonMarketplaceTableName,
            ['is_business_available' => 1],
            sprintf('marketplace_id IN (%s)', implode(',', self::B2B_MARKETPLACES))
        );
    }
}

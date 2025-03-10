<?php

declare(strict_types=1);

namespace Ess\M2ePro\Setup\Update\y25_m02;

use Ess\M2ePro\Model\ResourceModel\Amazon\Marketplace as AmazonMarketplace;

class DisableB2BForSomeAmazonMarketplaces extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    private const MARKETPLACE_IDS_OF_UNCHANGED = [
        24, // Canada
        25, // Germany
        26, // France
        28, // United Kingdom
        29, // United States
        30, // Spain
        31, // Italy
        34, // Mexico
        42, // Japan
        46, // India
    ];

    public function execute()
    {
        $this->getConnection()->update(
            $this->getFullTableName(\Ess\M2ePro\Helper\Module\Database\Tables::TABLE_AMAZON_MARKETPLACE),
            [AmazonMarketplace::COLUMN_IS_BUSINESS_AVAILABLE => false],
            [AmazonMarketplace::COLUMN_MARKETPLACE_ID . ' NOT IN (?)' => self::MARKETPLACE_IDS_OF_UNCHANGED]
        );
    }
}

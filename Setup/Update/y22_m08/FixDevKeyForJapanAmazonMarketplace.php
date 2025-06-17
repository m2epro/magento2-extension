<?php

namespace Ess\M2ePro\Setup\Update\y22_m08;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

class FixDevKeyForJapanAmazonMarketplace extends AbstractFeature
{
    public function execute()
    {
        $this->getConnection()->update(
            $this->getFullTableName('amazon_marketplace'),
            ['developer_key' => "2770-5005-3793"],
            '`marketplace_id` = 42'
        );
    }
}

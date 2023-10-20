<?php

namespace Ess\M2ePro\Setup\Update\y23_m10;

class EnableEbayShippingRate extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    public function execute(): void
    {
        $table = $this->getFullTableName('m2epro_ebay_marketplace');

        $this->getConnection()->update(
            $table,
            ['is_local_shipping_rate_table' => 1,
             'is_international_shipping_rate_table' => 1,
            ],
            ['marketplace_id IN (?)' => [7, 13]]
        );
    }
}

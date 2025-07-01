<?php

declare(strict_types=1);

namespace Ess\M2ePro\Setup\Update\y25_m06;

class FixValueOfProductTypeIdInWalmartListingProduct extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    public function execute()
    {
        $this->getConnection()->update(
            $this->getFullTableName(\Ess\M2ePro\Helper\Module\Database\Tables::TABLE_WALMART_LISTING_PRODUCT),
            ['product_type_id' => null],
            'product_type_id = 0'
        );
    }
}

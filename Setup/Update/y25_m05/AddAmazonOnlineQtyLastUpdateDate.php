<?php

declare(strict_types=1);

namespace Ess\M2ePro\Setup\Update\y25_m05;

class AddAmazonOnlineQtyLastUpdateDate extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    public function execute()
    {
        $modifier = $this
            ->getTableModifier(\Ess\M2ePro\Helper\Module\Database\Tables::TABLE_AMAZON_LISTING_PRODUCT);

        $modifier->addColumn(
            \Ess\M2ePro\Model\ResourceModel\Amazon\Listing\Product::COLUMN_ONLINE_QTY_LAST_UPDATE_DATE,
            'DATETIME',
            null,
            null,
            false,
            false
        );

        $modifier->commit();
    }
}

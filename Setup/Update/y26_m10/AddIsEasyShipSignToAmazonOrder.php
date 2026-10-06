<?php

declare(strict_types=1);

namespace Ess\M2ePro\Setup\Update\y26_m10;

use Ess\M2ePro\Helper\Module\Database\Tables;

class AddIsEasyShipSignToAmazonOrder extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    public function execute(): void
    {
        $modifier = $this->getTableModifier(Tables::TABLE_AMAZON_ORDER);

        $modifier->addColumn(
            \Ess\M2ePro\Model\ResourceModel\Amazon\Order::COLUMN_IS_EASY_SHIP,
            'SMALLINT UNSIGNED NOT NULL',
            0,
            null,
            false,
            false
        );

        $modifier->commit();
    }
}

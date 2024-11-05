<?php

declare(strict_types=1);

namespace Ess\M2ePro\Setup\Update\y24_m11;

use Ess\M2ePro\Helper\Module\Database\Tables;

class AddDeliveryDateFromColumnToAmazonOrder extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    public function execute()
    {
        $modifier = $this->getTableModifier(Tables::TABLE_AMAZON_ORDER);

        $modifier->addColumn(
            \Ess\M2ePro\Model\ResourceModel\Amazon\Order::COLUMN_DELIVERY_DATE_FROM,
            'DATETIME',
            null,
            null,
            false,
            false
        );

        $modifier->commit();
    }
}

<?php

declare(strict_types=1);

namespace Ess\M2ePro\Setup\Update\y26_m01;

use Ess\M2ePro\Helper\Module\Database\Tables;

class EbayAddIsFullRefundToOrder extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    public function execute(): void
    {
        $modifier = $this->getTableModifier(Tables::TABLE_EBAY_ORDER);
        $modifier->addColumn(
            \Ess\M2ePro\Model\ResourceModel\Ebay\Order::COLUMN_IS_FULL_REFUNDED,
            'SMALLINT UNSIGNED NOT NULL',
            '0',
            null,
            false,
            false
        );
        $modifier->commit();
    }
}

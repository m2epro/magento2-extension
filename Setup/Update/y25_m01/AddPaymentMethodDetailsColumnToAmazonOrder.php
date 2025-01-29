<?php

declare(strict_types=1);

namespace Ess\M2ePro\Setup\Update\y25_m01;

use Ess\M2ePro\Helper\Module\Database\Tables;

class AddPaymentMethodDetailsColumnToAmazonOrder extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    public function execute(): void
    {
        $modifier = $this->getTableModifier(Tables::TABLE_AMAZON_ORDER);

        $modifier->addColumn(
            \Ess\M2ePro\Model\ResourceModel\Amazon\Order::COLUMN_PAYMENT_METHOD_DETAILS,
            'LONGTEXT',
            null,
            null,
            false,
            false
        );

        $modifier->commit();
    }
}

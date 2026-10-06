<?php

declare(strict_types=1);

namespace Ess\M2ePro\Setup\Update\y26_m09;

use Ess\M2ePro\Helper\Module\Database\Tables;
use Magento\Framework\DB\Ddl\Table;

class AmazonRetrieveBillingAddress extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    public function execute(): void
    {
        $this->addBillingAddressColumnToAmazonOrderTable();
    }

    public function addBillingAddressColumnToAmazonOrderTable(): void
    {
        $modifier = $this->getTableModifier(Tables::TABLE_AMAZON_ORDER);

        $modifier->addColumn(
            \Ess\M2ePro\Model\ResourceModel\Amazon\Order::COLUMN_BILLING_ADDRESS,
            'LONGTEXT',
            null,
            null,
            false,
            false
        );

        $modifier->commit();
    }
}

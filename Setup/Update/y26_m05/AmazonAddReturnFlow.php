<?php

declare(strict_types=1);

namespace Ess\M2ePro\Setup\Update\y26_m05;

use Ess\M2ePro\Helper\Module\Database\Tables;

class AmazonAddReturnFlow extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    public function execute()
    {
        $this->addColumnToAmazonAccountTable();
        $this->addColumnToAmazonOrderItemsTable();
    }

    private function addColumnToAmazonAccountTable(): void
    {
        $modifier = $this->getTableModifier(Tables::TABLE_AMAZON_ACCOUNT);

        $modifier->addColumn(
            \Ess\M2ePro\Model\ResourceModel\Amazon\Account::COLUMN_ORDER_RETURN_DATA_LAST_SYNCHRONIZATION,
            'VARCHAR(255)',
            'NULL',
            \Ess\M2ePro\Model\ResourceModel\Amazon\Account::COLUMN_MULTI_LOCATION_INVENTORY_MAPPING,
            false,
            false
        );

        $modifier->commit();
    }

    private function addColumnToAmazonOrderItemsTable(): void
    {
        $modifier = $this->getTableModifier(Tables::TABLE_AMAZON_ORDER_ITEM);

        $modifier->addColumn(
            \Ess\M2ePro\Model\ResourceModel\Amazon\Order\Item::COLUMN_RETURN_REQUEST_DATE,
            'DATETIME',
            'NULL',
            null,
            false,
            false
        );

        $modifier->addColumn(
            \Ess\M2ePro\Model\ResourceModel\Amazon\Order\Item::COLUMN_RETURN_REQUEST_STATUS,
            'VARCHAR(255)',
            'NULL',
            null,
            false,
            false
        );

        $modifier->addColumn(
            \Ess\M2ePro\Model\ResourceModel\Amazon\Order\Item::COLUMN_RETURN_TRACKING_ID,
            'VARCHAR(255)',
            'NULL',
            null,
            false,
            false
        );

        $modifier->addColumn(
            \Ess\M2ePro\Model\ResourceModel\Amazon\Order\Item::COLUMN_RETURN_QTY,
            'INT UNSIGNED',
            'NULL',
            null,
            false,
            false
        );

        $modifier->addColumn(
            \Ess\M2ePro\Model\ResourceModel\Amazon\Order\Item::COLUMN_RETURN_RESOLUTION,
            'VARCHAR(255)',
            'NULL',
            null,
            false,
            false
        );

        $modifier->commit();
    }
}

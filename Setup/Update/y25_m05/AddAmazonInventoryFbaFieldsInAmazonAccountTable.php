<?php

declare(strict_types=1);

namespace Ess\M2ePro\Setup\Update\y25_m05;

use Ess\M2ePro\Helper\Module\Database\Tables;

class AddAmazonInventoryFbaFieldsInAmazonAccountTable extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    public function execute()
    {
        $this->addColumnsToAmazonAccount();
        $this->migrateFBASettings();
        $this->removeOldSettingsTable();
    }

    private function addColumnsToAmazonAccount(): void
    {
        $this->getTableModifier(Tables::TABLE_AMAZON_ACCOUNT)
             ->addColumn(
                 \Ess\M2ePro\Model\ResourceModel\Amazon\Account::COLUMN_FBA_INVENTORY_MODE,
                 'SMALLINT UNSIGNED NOT NULL',
                 0,
                 'info',
                 false,
                 false
             )
             ->addColumn(
                 \Ess\M2ePro\Model\ResourceModel\Amazon\Account::COLUMN_FBA_INVENTORY_SOURCE_NAME,
                 'VARCHAR(255)',
                 'NULL',
                 \Ess\M2ePro\Model\ResourceModel\Amazon\Account::COLUMN_FBA_INVENTORY_MODE,
                 false,
                 false
             )
             ->commit();
    }

    private function migrateFBASettings(): void
    {
        $connection = $this->getConnection();
        $sourceTable = $this->getFullTableName('amazon_account_merchant_setting');
        $destinationTable = $this->getFullTableName(Tables::TABLE_AMAZON_ACCOUNT);

        $query = $connection->select()
                            ->from(
                                $sourceTable,
                                ['merchant_id', 'fba_inventory_mode', 'fba_inventory_source_name']
                            );

        $settings = $connection->fetchAll($query);

        foreach ($settings as $setting) {
            $connection->update(
                $destinationTable,
                [
                    'fba_inventory_mode' => (int)$setting['fba_inventory_mode'],
                    'fba_inventory_source_name' => $setting['fba_inventory_source_name'],
                ],
                ['merchant_id = ?' => $setting['merchant_id']]
            );
        }
    }

    private function removeOldSettingsTable(): void
    {
        $this->getConnection()
             ->dropTable($this->getFullTableName('amazon_account_merchant_setting'));
    }
}

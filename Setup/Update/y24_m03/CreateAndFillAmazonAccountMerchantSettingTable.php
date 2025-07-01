<?php

declare(strict_types=1);

namespace Ess\M2ePro\Setup\Update\y24_m03;

class CreateAndFillAmazonAccountMerchantSettingTable extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    public function execute(): void
    {
        $this->createMerchantSettingTable();
        $this->fillMerchantSettingTable();
        $this->dropColumnsFbaInventoryFromAmazonAccountTable();
    }

    private function createMerchantSettingTable(): void
    {
        $amazonAccountMerchantSettingTableName = $this->getFullTableName('amazon_account_merchant_setting');
        $amazonAccountMerchantSettingTable = $this->getConnection()->newTable($amazonAccountMerchantSettingTableName);
        $amazonAccountMerchantSettingTable->addColumn(
            'merchant_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => false, 'primary' => true]
        );
        $amazonAccountMerchantSettingTable->addColumn(
            'fba_inventory_mode',
            \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
            null,
            ['unsigned' => true, 'nullable' => false, 'default' => 0]
        );
        $amazonAccountMerchantSettingTable->addColumn(
            'fba_inventory_source_name',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['default' => null]
        );
        $amazonAccountMerchantSettingTable->addColumn(
            'update_date',
            \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,
            null,
            ['default' => null]
        );
        $amazonAccountMerchantSettingTable->addColumn(
            'create_date',
            \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,
            null,
            ['default' => null]
        );

        $amazonAccountMerchantSettingTable->setOption('type', 'INNODB');
        $amazonAccountMerchantSettingTable->setOption('charset', 'utf8');
        $amazonAccountMerchantSettingTable->setOption('collate', 'utf8_general_ci');
        $amazonAccountMerchantSettingTable->setOption('row_format', 'dynamic');

        $this->getConnection()->createTable($amazonAccountMerchantSettingTable);
    }

    private function fillMerchantSettingTable(): void
    {
        $amazonAccountTableModifier = $this->getTableModifier(
            \Ess\M2ePro\Helper\Module\Database\Tables::TABLE_AMAZON_ACCOUNT
        );

        if (
            !$amazonAccountTableModifier->isColumnExists('fba_inventory_mode')
            || !$amazonAccountTableModifier->isColumnExists('fba_inventory_source')
        ) {
            return;
        }

        $amazonAccountTableName = $this->getFullTableName(
            \Ess\M2ePro\Helper\Module\Database\Tables::TABLE_AMAZON_ACCOUNT
        );
        $amazonAccountMerchantSettingTableName = $this->getFullTableName('amazon_account_merchant_setting');

        $amazonAccountData = $this->getConnection()
                                  ->query(
                                      sprintf(
                                          'SELECT merchant_id,
                                                  fba_inventory_mode,
                                                  fba_inventory_source
                                           FROM %s
                                           WHERE merchant_id NOT IN (SELECT merchant_id FROM %s)',
                                          $amazonAccountTableName,
                                          $amazonAccountMerchantSettingTableName
                                      )
                                  )
                                  ->fetchAll();

        if (empty($amazonAccountData)) {
            return;
        }

        $insertMerchantSettingData = [];
        $date = \Ess\M2ePro\Helper\Date::createCurrentGmt()->format('Y-m-d H:i:s');
        foreach ($amazonAccountData as $amazonAccountDatum) {
            $merchantId = $amazonAccountDatum['merchant_id'];
            if (
                isset($insertMerchantSettingData[$merchantId]['fba_inventory_mode'])
                && $insertMerchantSettingData[$merchantId]['fba_inventory_mode'] == 1
            ) {
                continue;
            }

            $insertMerchantSettingData[$merchantId] = [
                'merchant_id' => $merchantId,
                'fba_inventory_mode' => $amazonAccountDatum['fba_inventory_mode'],
                'fba_inventory_source_name' => $amazonAccountDatum['fba_inventory_source'],
                'create_date' => $date,
                'update_date' => $date,
            ];
        }

        foreach (array_chunk($insertMerchantSettingData, 100) as $chunk) {
            $this->getConnection()->insertMultiple($amazonAccountMerchantSettingTableName, $chunk);
        }
    }

    private function dropColumnsFbaInventoryFromAmazonAccountTable(): void
    {
        $amazonAccountTableModifier = $this->getTableModifier(
            \Ess\M2ePro\Helper\Module\Database\Tables::TABLE_AMAZON_ACCOUNT
        );
        $amazonAccountTableModifier->dropColumn('fba_inventory_mode');
        $amazonAccountTableModifier->dropColumn('fba_inventory_source');
    }
}

<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2016 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\UpgradeData\v1_3_1__v1_3_2;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;
use Magento\Framework\DB\Ddl\Table;

class AmazonSkusQueue extends AbstractFeature
{
    //########################################

    public function getBackupTables()
    {
        return ['lock_item', 'account'];
    }

    public function execute()
    {
        $amazonProcessingActionListSku = $this->getConnection()->newTable(
            $this->getFullTableName('amazon_processing_action_list_sku')
        )
            ->addColumn(
                'id', Table::TYPE_INTEGER, NULL,
                ['unsigned' => true, 'primary' => true, 'nullable' => false, 'auto_increment' => true]
            )
            ->addColumn(
                'account_id', Table::TYPE_INTEGER, NULL,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'sku', Table::TYPE_TEXT, 255,
                ['nullable' => false]
            )
            ->addColumn(
                'create_date', Table::TYPE_DATETIME, NULL,
                ['default' => NULL]
            )
            ->addIndex(
                'account_id__sku', ['account_id', 'sku'],
                ['type' => \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE]
            )
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($amazonProcessingActionListSku);

        $lockItemTable = $this->getFullTableName('lock_item');
        $skuQueueLockItemsStmt = $this->getConnection()->query("
          SELECT * FROM {$lockItemTable} WHERE `nick` LIKE 'amazon_list_skus_queue_%';
        ");

        $accountsTable = $this->getFullTableName('account');
        $amazonAccountsIds = $this->getConnection()->query("
          SELECT `id` FROM {$accountsTable} WHERE `component_mode` = 'amazon';
        ")->fetchAll(\PDO::FETCH_COLUMN);

        $amazonProcessingListSkuTable = $this->getFullTableName('amazon_processing_action_list_sku');

        $lockItemsIds = array();

        while ($lockItemData = $skuQueueLockItemsStmt->fetch(\PDO::FETCH_ASSOC)) {
            $lockItemsIds[] = $lockItemData['id'];

            $accountId = str_replace('amazon_list_skus_queue_', '', $lockItemData['nick']);
            if (!in_array($accountId, $amazonAccountsIds)) {
                continue;
            }

            $skus = @json_decode($lockItemData['data'], true);
            if (empty($skus)) {
                continue;
            }

            $insertData = array();

            foreach (array_unique($skus) as $sku) {
                $insertData[] = array(
                    'account_id' => $accountId,
                    'sku'        => $sku,
                );
            }

            $this->getConnection()->insertMultiple($amazonProcessingListSkuTable, $insertData);
        }

        if (!empty($lockItemsIds)) {
            $this->getConnection()->delete($lockItemTable, array('id IN (?)' => array_unique($lockItemsIds)));
        }
    }

    //########################################
}
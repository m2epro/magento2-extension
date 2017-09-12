<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2016 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\UpgradeData\v1_3_1__v1_3_2;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;
use Ess\M2ePro\Setup\InstallSchema;
use Magento\Framework\DB\Ddl\Table;

class ArchivedEntity extends AbstractFeature
{
    //########################################

    public function getBackupTables()
    {
        return ['module_config'];
    }

    public function execute()
    {
        $archivedEntity = $this->getConnection()->newTable(
            $this->getFullTableName('archived_entity')
        )
            ->addColumn(
                'id', Table::TYPE_INTEGER, NULL,
                ['unsigned' => true, 'primary' => true, 'nullable' => false, 'auto_increment' => true]
            )
            ->addColumn(
                'origin_id', Table::TYPE_INTEGER, NULL,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'name', Table::TYPE_TEXT, 255,
                ['nullable' => false]
            )
            ->addColumn(
                'data', Table::TYPE_TEXT, InstallSchema::LONG_COLUMN_SIZE,
                ['nullable' => false]
            )
            ->addColumn(
                'create_date', Table::TYPE_DATETIME, NULL,
                ['default' => NULL]
            )
            ->addIndex('origin_id__name', ['origin_id', 'name'])
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($archivedEntity);

        //----------------------------------------

        $this->getConfigModifier('module')->insert(
            '/cron/task/archive_orders_entities/', 'mode', '1', '0 - disable, \r\n1 - enable'
        );
        $this->getConfigModifier('module')->insert(
            '/cron/task/archive_orders_entities/', 'interval', '3600', 'in seconds'
        );
        $this->getConfigModifier('module')->insert(
            '/cron/task/archive_orders_entities/', 'last_access', NULL, 'date of last access'
        );
        $this->getConfigModifier('module')->insert(
            '/cron/task/archive_orders_entities/', 'last_run', NULL, 'date of last run'
        );
    }

    //########################################
}
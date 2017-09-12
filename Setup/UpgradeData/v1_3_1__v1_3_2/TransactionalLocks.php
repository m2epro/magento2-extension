<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2016 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\UpgradeData\v1_3_1__v1_3_2;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;
use Magento\Framework\DB\Ddl\Table;

class TransactionalLocks extends AbstractFeature
{
    //########################################

    public function getBackupTables()
    {
        return [];
    }

    public function execute()
    {
        $lockTransactional = $this->getConnection()->newTable(
            $this->getFullTableName('lock_transactional')
        )
            ->addColumn(
                'id', Table::TYPE_INTEGER, NULL,
                ['unsigned' => true, 'primary' => true, 'nullable' => false, 'auto_increment' => true]
            )
            ->addColumn(
                'nick', Table::TYPE_TEXT, 255,
                ['nullable' => false]
            )
            ->addColumn(
                'create_date', Table::TYPE_DATETIME, NULL,
                ['default' => NULL]
            )
            ->addIndex('nick', 'nick')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($lockTransactional);
    }

    //########################################
}
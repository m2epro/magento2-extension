<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Upgrade\v1_1_0__v1_2_0;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;
use Magento\Framework\DB\Ddl\Table;

class PublicVersionsChecker extends AbstractFeature
{
    //########################################

    public function execute()
    {
        if (!$this->getConnection()->isTableExists($this->getFullTableName('versions_history'))) {

            $table = $this->getConnection()->newTable($this->getFullTableName('versions_history'))
                ->addColumn(
                    'id', Table::TYPE_INTEGER, NULL,
                    ['unsigned' => true, 'primary' => true, 'nullable' => false, 'auto_increment' => true]
                )
                ->addColumn(
                    'version_from', Table::TYPE_TEXT, 32,
                    ['default' => NULL]
                )
                ->addColumn(
                    'version_to', Table::TYPE_TEXT, 32,
                    ['nullable' => false]
                )
                ->addColumn(
                    'update_date', Table::TYPE_DATETIME, NULL,
                    ['default' => NULL]
                )
                ->addColumn(
                    'create_date', Table::TYPE_DATETIME, NULL,
                    ['default' => NULL]
                )
                ->setOption('type', 'INNODB')
                ->setOption('charset', 'utf8')
                ->setOption('collate', 'utf8_general_ci');
            $this->getConnection()->createTable($table);
        }
    }

    //########################################
}
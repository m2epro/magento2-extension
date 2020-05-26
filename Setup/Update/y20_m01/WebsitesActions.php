<?php

namespace Ess\M2ePro\Setup\Update\y20_m01;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;
use Magento\Framework\DB\Ddl\Table;

/**
 * Class \Ess\M2ePro\Setup\Update\y20_m01\WebsitesActions
 */
class WebsitesActions extends AbstractFeature
{
    //########################################

    public function execute()
    {
        if ($this->installer->tableExists($this->getFullTableName('magento_product_websites_update'))) {
            return;
        }

        $magentoProductWebsitesUpdateTable = $this->getConnection()
            ->newTable($this->getFullTableName('magento_product_websites_update'))
            ->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'primary' => true, 'nullable' => false, 'auto_increment' => true]
            )
            ->addColumn(
                'product_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'action',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'website_id',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'create_date',
                Table::TYPE_DATETIME,
                null,
                ['default' => null]
            )
            ->addIndex('product_id', 'product_id')
            ->addIndex('action', 'action')
            ->addIndex('create_date', 'create_date')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($magentoProductWebsitesUpdateTable);
    }

    //########################################
}

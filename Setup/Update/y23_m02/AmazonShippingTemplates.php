<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Update\y23_m02;

use Magento\Framework\DB\Ddl\Table;

class AmazonShippingTemplates extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    /**
     * @return void
     * @throws \Ess\M2ePro\Model\Exception\Setup
     * @throws \Zend_Db_Exception
     */
    public function execute(): void
    {
        $this->truncateAmazonTemplateShipping();
        $this->clearTemplateShippingId();
        $this->changeAmazonTemplateShipping();
        $this->createAmazonDictionaryTemplateShipping();
    }

    /**
     * @return void
     */
    private function truncateAmazonTemplateShipping(): void
    {
        $this->installer->getConnection()->truncateTable(
            $this->getFullTableName('amazon_template_shipping')
        );
    }

    /**
     * @return void
     * @throws \Zend_Db_Adapter_Exception
     */
    private function clearTemplateShippingId(): void
    {
        $this->getConnection()->update(
            $this->getFullTableName('amazon_listing'),
            ['template_shipping_id' => 0]
        );

        $this->getConnection()->update(
            $this->getFullTableName('amazon_listing_product'),
            ['template_shipping_id' => '']
        );
    }

    /**
     * @return void
     * @throws \Ess\M2ePro\Model\Exception\Setup
     */
    private function changeAmazonTemplateShipping(): void
    {
        $tableModifier = $this->getTableModifier('amazon_template_shipping');
        if (!$tableModifier->isColumnExists('account_id')) {
            $tableModifier
                ->addColumn(
                    'account_id',
                    'INT UNSIGNED NOT NULL',
                    null,
                    'title',
                    false,
                    false
                )
                ->commit();
        }

        if (!$tableModifier->isColumnExists('marketplace_id')) {
            $tableModifier
                ->addColumn(
                    'marketplace_id',
                    'INT UNSIGNED NOT NULL',
                    null,
                    'account_id',
                    false,
                    false
                )
                ->commit();
        }

        if (!$tableModifier->isColumnExists('template_id')) {
            $tableModifier
                ->addColumn(
                    'template_id',
                    'VARCHAR(255) NOT NULL',
                    null,
                    'marketplace_id',
                    false,
                    false
                )
                ->commit();
        }

        $tableModifier
             ->dropColumn('template_name_mode', true, false)
             ->dropColumn('template_name_value', true, false)
             ->dropColumn('template_name_attribute', true, false)
             ->commit();
    }

    /**
     * @return void
     * @throws \Zend_Db_Exception
     */
    private function createAmazonDictionaryTemplateShipping(): void
    {
        if (!$this->installer->tableExists($this->getFullTableName('amazon_dictionary_template_shipping'))) {
            $dictionaryTemplateShippingTable = $this->getConnection()
               ->newTable($this->getFullTableName('amazon_dictionary_template_shipping'))
               ->addColumn(
                   'id',
                   Table::TYPE_INTEGER,
                   null,
                   [
                       'unsigned' => true,
                       'primary' => true,
                       'nullable' => false,
                       'auto_increment' => true,
                   ]
               )
                ->addColumn(
                    'account_id',
                    Table::TYPE_INTEGER,
                    null,
                    ['unsigned' => true, 'nullable' => false]
                )
               ->addColumn(
                   'template_id',
                   Table::TYPE_TEXT,
                   255,
                   ['nullable' => false]
               )
               ->addColumn(
                   'title',
                   Table::TYPE_TEXT,
                   255,
                   ['nullable' => false]
               )
               ->addIndex('account_id', 'account_id')
               ->setOption('type', 'INNODB')
               ->setOption('charset', 'utf8')
               ->setOption('collate', 'utf8_general_ci')
               ->setOption('row_format', 'dynamic');
            $this->getConnection()->createTable($dictionaryTemplateShippingTable);
        }
    }
}

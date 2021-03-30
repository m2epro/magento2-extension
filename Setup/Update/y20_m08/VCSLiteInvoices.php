<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Update\y20_m08;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;
use Magento\Framework\DB\Ddl\Table;

/**
 * Class Ess\M2ePro\Setup\Update\y20_m08\VCSLiteInvoices
 */
class VCSLiteInvoices extends AbstractFeature
{
    //########################################

    public function execute()
    {
        $amazonOrderInvoiceTable = $this->getFullTableName('amazon_order_invoice');

        if (!$this->installer->tableExists($amazonOrderInvoiceTable)) {
            $amazonOrderInvoice = $this->getConnection()->newTable($amazonOrderInvoiceTable)
                ->addColumn(
                    'id',
                    Table::TYPE_INTEGER,
                    null,
                    ['unsigned' => true, 'primary' => true, 'nullable' => false, 'auto_increment' => true]
                )
                ->addColumn(
                    'order_id',
                    Table::TYPE_INTEGER,
                    null,
                    ['unsigned' => true, 'nullable' => false]
                )
                ->addColumn(
                    'document_type',
                    Table::TYPE_TEXT,
                    64,
                    ['default' => null]
                )
                ->addColumn(
                    'document_number',
                    Table::TYPE_TEXT,
                    64,
                    ['default' => null]
                )
                ->addColumn(
                    'document_data',
                    Table::TYPE_TEXT,
                    null,
                    ['default' => null]
                )
                ->addColumn(
                    'update_date',
                    Table::TYPE_DATETIME,
                    null,
                    ['default' => null]
                )
                ->addColumn(
                    'create_date',
                    Table::TYPE_DATETIME,
                    null,
                    ['default' => null]
                )
                ->addIndex('order_id', 'order_id')
                ->setOption('type', 'INNODB')
                ->setOption('charset', 'utf8')
                ->setOption('collate', 'utf8_general_ci');
            $this->getConnection()->createTable($amazonOrderInvoice);
        }

        $this->getTableModifier('amazon_account')
            ->addColumn(
                'invoice_generation', 'SMALLINT(5) UNSIGNED NOT NULL', 0, 'auto_invoicing', false, false
            )
            ->addColumn(
                'create_magento_shipment',
                'SMALLINT(5) UNSIGNED NOT NULL',
                1,
                'is_magento_invoice_creation_disabled',
                false,
                false
            )
            ->commit();


        $this->getTableModifier('amazon_order')
            ->addColumn(
                'invoice_data_report', 'LONGTEXT', 'NULL', 'is_credit_memo_sent', false, false
            )
            ->commit();

        if ($this->getTableModifier('amazon_account')
            ->isColumnExists('is_magento_invoice_creation_disabled')) {

            $dataHelper = $this->helperFactory->getObject('Data');

            $amazonAccountTable = $this->getFullTableName('amazon_account');

            $query = $this->getConnection()
                ->select()
                ->from($amazonAccountTable)
                ->query();

            while ($row = $query->fetch()) {
                $magentoOrdersSettings = $dataHelper->jsonDecode($row['magento_orders_settings']);

                $data = [
                    'is_magento_invoice_creation_disabled' => isset($magentoOrdersSettings['invoice_mode']) ?
                        $magentoOrdersSettings['invoice_mode'] : 0,
                    'create_magento_shipment'              => isset($magentoOrdersSettings['shipment_mode']) ?
                        $magentoOrdersSettings['invoice_mode'] : 0
                ];

                // clearing old data
                unset($magentoOrdersSettings['invoice_mode']);
                unset($magentoOrdersSettings['shipment_mode']);
                $data['magento_orders_settings'] = $dataHelper->jsonEncode($magentoOrdersSettings);

                $this->getConnection()->update(
                    $amazonAccountTable,
                    $data,
                    ['account_id = ?' => $row['account_id']]
                );
            }

            $this->getTableModifier('amazon_account')
                ->changeColumn('is_magento_invoice_creation_disabled', 'SMALLINT(5) UNSIGNED NOT NULL', 1, null, false)
                ->commit();
            $this->getTableModifier('amazon_account')
                ->renameColumn('is_magento_invoice_creation_disabled', 'create_magento_invoice', false, false)
                ->commit();
        }
    }

    //########################################
}

<?php

declare(strict_types=1);

namespace Ess\M2ePro\Setup\Update\y24_m10;

use Ess\M2ePro\Helper\Module\Database\Tables as TablesHelper;
use Ess\M2ePro\Model\ResourceModel\Ebay\ComplianceDocuments as ComplianceDocumentsResource;
use Ess\M2ePro\Model\ResourceModel\Ebay\ComplianceDocuments\ListingProductRelation as ListingProductRelationResource;
use Magento\Framework\DB\Ddl\Table;

class AddEbayComplianceDocuments extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    public function execute(): void
    {
        $this->createEbayComplianceDocumentsTable();
        $this->createEbayComplianceDocumentListingProductTable();
        $this->addColumnToDescriptionPolicy();
        $this->addColumnToEbayListingProduct();
    }

    private function createEbayComplianceDocumentsTable()
    {
        $tableName = $this->getFullTableName(TablesHelper::TABLE_EBAY_COMPLIANCE_DOCUMENTS);

        $table = $this->getConnection()->newTable($tableName);

        $table
            ->addColumn(
                ComplianceDocumentsResource::COLUMN_ID,
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'primary' => true, 'nullable' => false, 'auto_increment' => true]
            )
            ->addColumn(
                ComplianceDocumentsResource::COLUMN_ACCOUNT_ID,
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                ComplianceDocumentsResource::COLUMN_HASH,
                Table::TYPE_TEXT,
                32,
                ['nullable' => false]
            )
            ->addColumn(
                ComplianceDocumentsResource::COLUMN_TYPE,
                Table::TYPE_TEXT,
                100,
                ['nullable' => false]
            )
            ->addColumn(
                ComplianceDocumentsResource::COLUMN_URL,
                Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                ComplianceDocumentsResource::COLUMN_STATUS,
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                ComplianceDocumentsResource::COLUMN_DOCUMENT_ID,
                Table::TYPE_TEXT,
                255
            )
            ->addColumn(
                ComplianceDocumentsResource::COLUMN_ERROR,
                Table::TYPE_TEXT,
                255
            )
            ->addColumn(
                ComplianceDocumentsResource::COLUMN_CREATE_DATE,
                Table::TYPE_DATETIME
            )
            ->addColumn(
                ComplianceDocumentsResource::COLUMN_UPDATE_DATE,
                Table::TYPE_DATETIME
            );

        $table
            ->addIndex(
                'account_id__type__url',
                [
                    ComplianceDocumentsResource::COLUMN_ACCOUNT_ID,
                    ComplianceDocumentsResource::COLUMN_TYPE,
                    ComplianceDocumentsResource::COLUMN_URL,
                ],
                ['type' => \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE]
            );

        $table
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');

        $this->getConnection()->createTable($table);
    }

    private function createEbayComplianceDocumentListingProductTable()
    {
        $tableName = $this->getFullTableName(TablesHelper::TABLE_EBAY_COMPLIANCE_DOCUMENTS_LISTING_PRODUCT);

        $table = $this->getConnection()->newTable($tableName);

        $table
            ->addColumn(
                ListingProductRelationResource::COLUMN_ID,
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'primary' => true, 'nullable' => false, 'auto_increment' => true]
            )
            ->addColumn(
                ListingProductRelationResource::COLUMN_COMPLIANCE_DOCUMENT_ID,
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                ListingProductRelationResource::COLUMN_LISTING_PRODUCT_ID,
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false]
            );

        $table
            ->addIndex('listing_product_id', ListingProductRelationResource::COLUMN_LISTING_PRODUCT_ID);

        $table
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');

        $this->getConnection()->createTable($table);
    }

    private function addColumnToDescriptionPolicy(): void
    {
        $modifier = $this->getTableModifier(TablesHelper::TABLE_EBAY_TEMPLATE_DESCRIPTION);
        $modifier->addColumn(
            \Ess\M2ePro\Model\ResourceModel\Ebay\Template\Description::COLUMN_COMPLIANCE_DOCUMENTS,
            'TEXT',
            null,
            null,
            false,
            false
        );
        $modifier->commit();
    }

    private function addColumnToEbayListingProduct()
    {
        $modifier = $this->getTableModifier(TablesHelper::TABLE_EBAY_LISTING_PRODUCT);
        $modifier->addColumn(
            \Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Product::COLUMN_COMPLIANCE_DOCUMENTS,
            'TEXT',
            null,
            null,
            false,
            false
        );
        $modifier->addColumn(
            \Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Product::COLUMN_ONLINE_COMPLIANCE_DOCUMENTS,
            'TEXT',
            null,
            null,
            false,
            false
        );
        $modifier->commit();
    }
}

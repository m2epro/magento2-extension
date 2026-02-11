<?php

declare(strict_types=1);

namespace Ess\M2ePro\Setup\Update\y26_m01;

use Ess\M2ePro\Helper\Module\Database\Tables;
use Ess\M2ePro\Model\ResourceModel\Walmart\Template\Repricer as TemplateRepricerResource;

class AddWalmartRepricerPolicy extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    public function execute(): void
    {
        $this->createWalmartTemplateRepricerTable();
        $this->addColumnTemplateRepricerIdToWalmartListingTable();
        $this->addColumnTemplateRepricerIdToWalmartListingProductTable();

        $transaction = $this->getConnection()->beginTransaction();
        try {
            $this->migrateRepricerSettings($transaction)->commit();
        } catch (\Throwable $throwable) {
            $transaction->rollBack();

            throw $throwable;
        }

        $this->deleteColumnsFromWalmartSellingFormatTable();
    }

    private function createWalmartTemplateRepricerTable(): void
    {
        $tableName = $this
            ->getFullTableName(Tables::TABLE_WALMART_TEMPLATE_REPRICER);

        $table = $this
            ->getConnection()
            ->newTable($tableName)
            ->addColumn(
                \Ess\M2ePro\Model\ResourceModel\Walmart\Template\Repricer::COLUMN_ID,
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'primary' => true, 'nullable' => false, 'auto_increment' => true]
            )
            ->addColumn(
                \Ess\M2ePro\Model\ResourceModel\Walmart\Template\Repricer::COLUMN_TITLE,
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['nullable' => false]
            )
            ->addColumn(
                \Ess\M2ePro\Model\ResourceModel\Walmart\Template\Repricer::COLUMN_ACCOUNT_ID,
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                \Ess\M2ePro\Model\ResourceModel\Walmart\Template\Repricer::COLUMN_MIN_PRICE_MODE,
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => '0']
            )
            ->addColumn(
                \Ess\M2ePro\Model\ResourceModel\Walmart\Template\Repricer::COLUMN_MIN_PRICE_ATTRIBUTE,
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                \Ess\M2ePro\Model\ResourceModel\Walmart\Template\Repricer::COLUMN_MAX_PRICE_MODE,
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => '0']
            )
            ->addColumn(
                \Ess\M2ePro\Model\ResourceModel\Walmart\Template\Repricer::COLUMN_MAX_PRICE_ATTRIBUTE,
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                \Ess\M2ePro\Model\ResourceModel\Walmart\Template\Repricer::COLUMN_STRATEGY_NAME,
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['default' => null]
            )
            ->addColumn(
                \Ess\M2ePro\Model\ResourceModel\Walmart\Template\Repricer::COLUMN_UPDATE_DATE,
                \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,
                null,
                ['default' => null]
            )
            ->addColumn(
                \Ess\M2ePro\Model\ResourceModel\Walmart\Template\Repricer::COLUMN_CREATE_DATE,
                \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,
                null,
                ['default' => null]
            )
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');

        $this->getConnection()->createTable($table);
    }

    private function addColumnTemplateRepricerIdToWalmartListingTable(): void
    {
        $modifier = $this->getTableModifier(Tables::TABLE_WALMART_LISTING);
        $modifier->addColumn(
            \Ess\M2ePro\Model\ResourceModel\Walmart\Listing::COLUMN_TEMPLATE_REPRICER_ID,
            'INT UNSIGNED',
            null,
            null,
            false,
            false
        );
        $modifier->commit();
    }

    private function addColumnTemplateRepricerIdToWalmartListingProductTable(): void
    {
        $modifier = $this->getTableModifier(Tables::TABLE_WALMART_LISTING_PRODUCT);
        $modifier->addColumn(
            \Ess\M2ePro\Model\ResourceModel\Walmart\Listing\Product::COLUMN_TEMPLATE_REPRICER_ID,
            'INT UNSIGNED',
            null,
            null,
            false,
            false
        );
        $modifier->commit();
    }

    private function migrateRepricerSettings(
        \Magento\Framework\DB\Adapter\Pdo\Mysql $transaction
    ): \Magento\Framework\DB\Adapter\Pdo\Mysql {
        $select = $transaction
            ->select()
            ->from(
                $this->getFullTableName(Tables::TABLE_WALMART_TEMPLATE_SELLING_FORMAT),
                [
                    'selling_format_id' => 'template_selling_format_id',
                    'strategies' => 'repricer_account_strategies',
                    'min_price_mode' => 'repricer_min_price_mode',
                    'min_price_attribute' => 'repricer_min_price_attribute',
                    'max_price_mode' => 'repricer_max_price_mode',
                    'max_price_attribute' => 'repricer_max_price_attribute',
                ]
            )
            ->where('repricer_account_strategies IS NOT NULL');

        $stmt = $select->query();

        $walmartTemplateRepricerLastId = $this->getWalmartTemplateRepricerLastId();
        $titleCounters = [];
        while ($row = $stmt->fetch()) {
            $strategies = json_decode($row['strategies'], true);
            foreach ($strategies as $strategy) {
                $title = $strategy['strategy_id'];
                if (!isset($titleCounters[$title])) {
                    $titleCounters[$title] = 0;
                } else {
                    ++$titleCounters[$title];
                    $title .= sprintf(' (%s)', $titleCounters[$title]);
                }

                $templateRepricerId = ++$walmartTemplateRepricerLastId;
                $templateSellingFormatId = (int)$row['selling_format_id'];
                $accountId = (int)$strategy['account_id'];

                $nowDate = \Ess\M2ePro\Helper\Date::createCurrentGmt()->format('Y-m-d H:i:s');

                $transaction->insert(
                    $this->getFullTableName(Tables::TABLE_WALMART_TEMPLATE_REPRICER),
                    [
                        TemplateRepricerResource::COLUMN_ID => $templateRepricerId,
                        TemplateRepricerResource::COLUMN_TITLE => $title,
                        TemplateRepricerResource::COLUMN_ACCOUNT_ID => $strategy['account_id'],
                        TemplateRepricerResource::COLUMN_STRATEGY_NAME => $strategy['strategy_id'],
                        TemplateRepricerResource::COLUMN_MIN_PRICE_MODE => $row['min_price_mode'],
                        TemplateRepricerResource::COLUMN_MIN_PRICE_ATTRIBUTE => $row['min_price_attribute'],
                        TemplateRepricerResource::COLUMN_MAX_PRICE_MODE => $row['max_price_mode'],
                        TemplateRepricerResource::COLUMN_MAX_PRICE_ATTRIBUTE => $row['max_price_attribute'],
                        TemplateRepricerResource::COLUMN_CREATE_DATE => $nowDate,
                        TemplateRepricerResource::COLUMN_UPDATE_DATE => $nowDate,
                    ]
                );

                // ----------------------------------------

                $listingSelect = $transaction
                    ->select()
                    ->from(['l' => $this->getFullTableName(Tables::TABLE_LISTING)], ['id'])
                    ->joinInner(
                        ['wl' => $this->getFullTableName(Tables::TABLE_WALMART_LISTING)],
                        'wl.listing_id = l.id',
                        []
                    )
                    ->where('wl.template_selling_format_id = ?', $templateSellingFormatId)
                    ->where('l.account_id = ?', $accountId);

                $listingIds = array_map('intval', $listingSelect->query()->fetchAll(\Zend_Db::FETCH_COLUMN));
                if (empty($listingIds)) {
                    continue;
                }

                $transaction
                    ->update(
                        $this->getFullTableName(Tables::TABLE_WALMART_LISTING),
                        [
                            \Ess\M2ePro\Model\ResourceModel\Walmart\Listing::COLUMN_TEMPLATE_REPRICER_ID => $templateRepricerId,
                        ],
                        sprintf('listing_id IN (%s)', implode(',', $listingIds))
                    );
            }
        }

        return $transaction;
    }

    private function getWalmartTemplateRepricerLastId(): int
    {
        return (int)$this
            ->getConnection()
            ->select()
            ->from($this->getFullTableName(Tables::TABLE_WALMART_TEMPLATE_REPRICER), [])
            ->columns([new \Zend_Db_Expr('MAX(id)')])
            ->query()
            ->fetchColumn();
    }

    private function deleteColumnsFromWalmartSellingFormatTable(): void
    {
        $modifier = $this->getTableModifier(Tables::TABLE_WALMART_TEMPLATE_SELLING_FORMAT);
        $modifier
            ->dropColumn('repricer_min_price_mode', false, false)
            ->dropColumn('repricer_min_price_attribute', false, false)
            ->dropColumn('repricer_max_price_mode', false, false)
            ->dropColumn('repricer_max_price_attribute', false, false)
            ->dropColumn('repricer_account_strategies', false, false);

        $modifier->commit();
    }
}

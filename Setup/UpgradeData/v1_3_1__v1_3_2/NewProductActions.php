<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2016 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\UpgradeData\v1_3_1__v1_3_2;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;
use Magento\Framework\DB\Ddl\Table;

class NewProductActions extends AbstractFeature
{
    const BACKUP_TABLE_IDENTIFIER_MAX_LEN = 20;
    const BACKUP_TABLE_SUFFIX             = '_b';

    const BACKUP_MARK_KEY = '_version_140_backuped';

    //########################################

    public function getBackupTables()
    {
        return ['processing'];
    }

    public function execute()
    {
        if ($this->isCompleted()) {
            return;
        }

        if (!$this->isMovedToBackup()) {
            $this->moveToBackup();
            $this->markAsMovedToBackup();
        }

        $this->prepareStructure();

        $processingsStmt = $this->getConnection()->select()
            ->from($this->getBackupTableName('processing'))
            ->query();

        while ($oldProcessingRow = $processingsStmt->fetch()) {

            if (strpos($oldProcessingRow['model'], 'Ebay\Connector\Item') !== false) {
                $this->processEbayItemProcessing($oldProcessingRow);
                continue;
            }

            if (strpos($oldProcessingRow['model'], 'Amazon\Connector\Product') !== false) {
                $this->processAmazonProductProcessing($oldProcessingRow);
                continue;
            }

            if (strpos($oldProcessingRow['model'], 'Amazon\Connector\Order') !== false) {
                $this->processAmazonOrderProcessing($oldProcessingRow);
                continue;
            }

            $newProcessingRow = $oldProcessingRow;
            unset($newProcessingRow['id']);

            $this->getConnection()->insert($this->getTableName('processing'), $newProcessingRow);

            $this->updateProcessingLocks($oldProcessingRow, $this->getConnection()->lastInsertId());
        }

        $this->removeBackup();
    }

    //########################################

    private function moveToBackup()
    {
        $this->moveTableToBackup('processing');

        $this->moveTableToBackup('ebay_processing_action');
        $this->moveTableToBackup('ebay_processing_action_item');

        $this->moveTableToBackup('amazon_processing_action');
        $this->moveTableToBackup('amazon_processing_action_item');
    }

    private function removeBackup()
    {
        $this->getConnection()->dropTable($this->getBackupTableName('processing'));

        $this->getConnection()->dropTable($this->getBackupTableName('ebay_processing_action'));
        $this->getConnection()->dropTable($this->getBackupTableName('ebay_processing_action_item'));

        $this->getConnection()->dropTable($this->getBackupTableName('amazon_processing_action'));
        $this->getConnection()->dropTable($this->getBackupTableName('amazon_processing_action_item'));
    }

    private function prepareStructure()
    {
        $this->getConnection()->dropTable($this->getTableName('processing'));
        $this->getConnection()->dropTable($this->getTableName('ebay_processing_action'));
        $this->getConnection()->dropTable($this->getTableName('amazon_processing_action'));

        $processingTable = $this->getConnection()->newTable($this->getTableName('processing'))
            ->addColumn(
                'id', Table::TYPE_INTEGER, NULL,
                ['unsigned' => true, 'primary' => true, 'nullable' => false, 'auto_increment' => true]
            )
            ->addColumn(
                'model', Table::TYPE_TEXT, 255,
                ['nullable' => false]
            )
            ->addColumn(
                'params', Table::TYPE_TEXT, \Ess\M2ePro\Setup\InstallSchema::LONG_COLUMN_SIZE,
                ['default' => NULL]
            )
            ->addColumn(
                'result_data', Table::TYPE_TEXT, \Ess\M2ePro\Setup\InstallSchema::LONG_COLUMN_SIZE,
                ['default' => NULL]
            )
            ->addColumn(
                'result_messages', Table::TYPE_TEXT, \Ess\M2ePro\Setup\InstallSchema::LONG_COLUMN_SIZE,
                ['default' => NULL]
            )
            ->addColumn(
                'is_completed', Table::TYPE_SMALLINT, NULL,
                ['nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'expiration_date', Table::TYPE_DATETIME, NULL,
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
            ->addIndex('model', 'model')
            ->addIndex('is_completed', 'is_completed')
            ->addIndex('expiration_date', 'expiration_date')
            ->setOption('type', 'MYISAM')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($processingTable);

        $ebayProcessingActionTable = $this->getConnection()->newTable($this->getTableName('ebay_processing_action'))
            ->addColumn(
                'id', Table::TYPE_INTEGER, NULL,
                ['unsigned' => true, 'primary' => true, 'nullable' => false, 'auto_increment' => true]
            )
            ->addColumn(
                'account_id', Table::TYPE_INTEGER, NULL,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'marketplace_id', Table::TYPE_INTEGER, NULL,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'processing_id', Table::TYPE_INTEGER, NULL,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'related_id', Table::TYPE_INTEGER, NULL,
                ['unsigned' => true, 'nullable' => true]
            )
            ->addColumn(
                'type', Table::TYPE_TEXT, 12,
                ['nullable' => false]
            )
            ->addColumn(
                'priority', Table::TYPE_INTEGER, NULL,
                ['unsigned' => true, 'nullable' => false, 'default' => 0]
            )
            ->addColumn(
                'request_timeout', Table::TYPE_INTEGER, NULL,
                ['unsigned' => true, 'default' => NULL]
            )
            ->addColumn(
                'request_data', Table::TYPE_TEXT, \Ess\M2ePro\Setup\InstallSchema::LONG_COLUMN_SIZE,
                ['nullable' => false]
            )
            ->addColumn(
                'start_date', Table::TYPE_DATETIME, NULL,
                ['default' => NULL]
            )
            ->addColumn(
                'update_date', Table::TYPE_DATETIME, NULL,
                ['default' => NULL]
            )
            ->addColumn(
                'create_date', Table::TYPE_DATETIME, NULL,
                ['default' => NULL]
            )
            ->addIndex('account_id', 'account_id')
            ->addIndex('marketplace_id', 'marketplace_id')
            ->addIndex('processing_id', 'processing_id')
            ->addIndex('type', 'type')
            ->addIndex('priority', 'priority')
            ->addIndex('start_date', 'start_date')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($ebayProcessingActionTable);

        $amazonProcessingActionTable = $this->getConnection()->newTable($this->getTableName('amazon_processing_action'))
            ->addColumn(
                'id', Table::TYPE_INTEGER, NULL,
                ['unsigned' => true, 'primary' => true, 'nullable' => false, 'auto_increment' => true]
            )
            ->addColumn(
                'account_id', Table::TYPE_INTEGER, NULL,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'processing_id', Table::TYPE_INTEGER, NULL,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'request_pending_single_id', Table::TYPE_INTEGER, NULL,
                ['unsigned' => true, 'nullable' => true]
            )
            ->addColumn(
                'related_id', Table::TYPE_INTEGER, NULL,
                ['unsigned' => true, 'nullable' => true]
            )
            ->addColumn(
                'type', Table::TYPE_TEXT, 12,
                ['nullable' => false]
            )
            ->addColumn(
                'request_data', Table::TYPE_TEXT, \Ess\M2ePro\Setup\InstallSchema::LONG_COLUMN_SIZE,
                ['nullable' => false]
            )
            ->addColumn(
                'start_date', Table::TYPE_DATETIME, NULL,
                ['default' => NULL]
            )
            ->addColumn(
                'update_date', Table::TYPE_DATETIME, NULL,
                ['default' => NULL]
            )
            ->addColumn(
                'create_date', Table::TYPE_DATETIME, NULL,
                ['default' => NULL]
            )
            ->addIndex('account_id', 'account_id')
            ->addIndex('processing_id', 'processing_id')
            ->addIndex('request_pending_single_id', 'request_pending_single_id')
            ->addIndex('related_id', 'related_id')
            ->addIndex('type', 'type')
            ->addIndex('start_date', 'start_date')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci');
        $this->getConnection()->createTable($amazonProcessingActionTable);

        $this->getTableModifier('listing_product')->addColumn(
            'need_synch_rules_check', 'SMALLINT(5) UNSIGNED NOT NULL', '0', 'additional_data', true
        );

        $this->getConnection()->dropTable($this->getTableName('ebay_processing_action_item'));
        $this->getConnection()->dropTable($this->getTableName('amazon_processing_action_item'));
    }

    //########################################

    private function isCompleted()
    {
        return !$this->getConnection()->isTableExists($this->getTableName('ebay_processing_action_item')) &&
            !$this->getConnection()->isTableExists($this->getBackupTableName('processing'));
    }

    //########################################

    private function isMovedToBackup()
    {
        if (!$this->getConnection()->isTableExists($this->getBackupTableName('ebay_processing_action_item'))) {
            return false;
        }

        $select = $this->getConnection()->select()
            ->from($this->getBackupTableName('ebay_processing_action_item'))
            ->order('id DESC')
            ->limit(1);

        $row = $this->getConnection()->fetchRow($select);

        if (empty($row['input_data'])) {
            return false;
        }

        $rowInputData = json_decode($row['input_data'], true);

        return !empty($rowInputData[self::BACKUP_MARK_KEY]);
    }

    private function markAsMovedToBackup()
    {
        $this->getConnection()->insert(
            $this->getBackupTableName('ebay_processing_action_item'),
            array(
                'action_id'  => 0,
                'related_id' => 0,
                'input_data' => json_encode(array(self::BACKUP_MARK_KEY => true)),
                'is_skipped' => 0,
            )
        );
    }

    //----------------------------------------

    private function moveTableToBackup($tableName)
    {
        if (!$this->getConnection()->isTableExists($this->getTableName($tableName))) {
            return;
        }

        if ($this->getConnection()->isTableExists($this->getBackupTableName($tableName))) {
            $this->getConnection()->dropTable($this->getBackupTableName($tableName));
        }

        $this->getConnection()->renameTable($this->getTableName($tableName), $this->getBackupTableName($tableName));
    }

    //########################################

    private function processEbayItemProcessing(array $oldProcessingRow)
    {
        if (!empty($oldProcessingRow['is_completed'])) {
            $isMultiple = strpos($oldProcessingRow['model'], 'Multiple') !== false;
            $oldProcessingParams = json_decode($oldProcessingRow['params'], true);

            if (!$isMultiple) {
                $listingsProductsIds = array($oldProcessingParams['listing_product_id']);
            } else {
                $listingsProductsIds = array_keys($oldProcessingParams['request_data']['items']);
            }

            foreach ($listingsProductsIds as $listingProductId) {
                $this->getConnection()->insert(
                    $this->getTableName('processing'),
                    $this->prepareEbayItemProcessingData($oldProcessingRow, $listingProductId)
                );

                $this->updateProcessingLocks(
                    $oldProcessingRow, $this->getConnection()->lastInsertId(), $listingProductId
                );
            }
        } else {
            $processingActionBackupTable = $this->getBackupTableName('ebay_processing_action');
            $processingActionItemBackupTable = $this->getBackupTableName('ebay_processing_action_item');
            $oldActionsData = $this->getConnection()->query("
SELECT `epab`.`account_id` AS `account_id`,
       `epab`.`marketplace_id` AS `marketplace_id`,
       `epab`.`type` AS `action_type`,
       `epab`.`request_timeout` AS `request_timeout`,
       `epab`.`update_date` AS `update_date`,
       `epab`.`create_date` AS `create_date`,
       `epaib`.`related_id` AS `related_id`,
       `epaib`.`input_data` AS `input_data`,
      `epaib`.`is_skipped` AS `is_skipped`
FROM `{$processingActionItemBackupTable}` AS `epaib`
LEFT JOIN `{$processingActionBackupTable}` AS `epab` ON `epab`.`id` = `epaib`.`action_id`
WHERE `epab`.`processing_id` = {$oldProcessingRow['id']}
            ")->fetchAll(\PDO::FETCH_ASSOC);

            foreach ($oldActionsData as $oldActionData) {
                $this->getConnection()->insert(
                    $this->getTableName('processing'),
                    $this->prepareEbayItemProcessingData($oldProcessingRow, $oldActionData['related_id'])
                );

                $newProcessingId = $this->getConnection()->lastInsertId();

                $this->updateProcessingLocks(
                    $oldProcessingRow, $newProcessingId, $oldActionData['related_id']
                );

                $newActionData = array(
                    'processing_id'   => $newProcessingId,
                    'account_id'      => $oldActionData['account_id'],
                    'marketplace_id'  => $oldActionData['marketplace_id'],
                    'related_id'      => $oldActionData['related_id'],
                    'type'            => $oldActionData['action_type'],
                    'request_timeout' => $oldActionData['request_timeout'],
                    'request_data'    => $oldActionData['input_data'],
                    'start_date'      => $oldActionData['create_date'],
                    'update_date'     => $oldActionData['update_date'],
                    'create_date'     => $oldActionData['create_date'],
                );

                $this->getConnection()->insert($this->getTableName('ebay_processing_action'), $newActionData);

                if (!empty($oldActionData['is_skipped'])) {
                    $this->getConnection()->update(
                        $this->getTableName('listing_product'),
                        array('need_synch_rules_check' => 1),
                        array('id = ?' => $oldActionData['related_id'])
                    );
                }
            }
        }

        $this->getConnection()->delete(
            $this->getTableName('processing_lock'),
            array('processing_id = ?' => $oldProcessingRow['id'])
        );
    }

    private function processAmazonProductProcessing(array $oldProcessingRow)
    {
        if (!empty($oldProcessingRow['is_completed'])) {
            $oldProcessingParams = json_decode($oldProcessingRow['params'], true);
            $listingsProductsIds = array_keys($oldProcessingParams['request_data']['items']);

            foreach ($listingsProductsIds as $listingProductId) {
                $this->getConnection()->insert(
                    $this->getTableName('processing'),
                    $this->prepareAmazonProductProcessingData($oldProcessingRow, $listingProductId)
                );

                $this->updateProcessingLocks(
                    $oldProcessingRow, $this->getConnection()->lastInsertId(), $listingProductId
                );
            }
        } else {
            $processingActionBackupTable = $this->getBackupTableName('amazon_processing_action');
            $processingActionItemBackupTable = $this->getBackupTableName('amazon_processing_action_item');

            $oldActionsData = $this->getConnection()->query("
SELECT `apab`.`account_id` AS `account_id`,
       `apab`.`type` AS `action_type`,
       `apab`.`update_date` AS `update_date`,
       `apab`.`create_date` AS `create_date`,
       `apaib`.`request_pending_single_id` AS `request_pending_single_id`,
       `apaib`.`related_id` AS `related_id`,
       `apaib`.`input_data` AS `input_data`,
       `apaib`.`output_data` AS `output_data`,
       `apaib`.`output_messages` AS `output_messages`,
       `apaib`.`is_skipped` AS `is_skipped`
FROM `{$processingActionItemBackupTable}` AS `apaib`
LEFT JOIN `{$processingActionBackupTable}` AS `apab` ON `apab`.`id` = `apaib`.`action_id`
WHERE `apab`.`processing_id` = {$oldProcessingRow['id']}
            ")->fetchAll(\PDO::FETCH_ASSOC);

            foreach ($oldActionsData as $oldActionData) {
                $listingProductId = $oldActionData['related_id'];

                $newProcessingData = $this->prepareAmazonProductProcessingData(
                    $oldProcessingRow, $oldActionData['related_id']
                );

                if (is_null($newProcessingData['result_data']) &&
                    !empty($oldActionData['is_completed']) &&
                    !empty($oldActionData['output_messages'])
                ) {
                    $newProcessingData['result_data'] = array(
                        'messages' => json_decode($oldActionData['output_messages'], true),
                    );

                    if (!empty($oldActionData['output_data'])) {
                        $newProcessingData['result_data'] = array_merge(
                            json_decode($oldActionData['output_data'], true), $newProcessingData['result_data']
                        );
                    }
                }

                $this->getConnection()->insert($this->getTableName('processing'), $newProcessingData);

                $newProcessingId = $this->getConnection()->lastInsertId();

                $this->updateProcessingLocks($oldProcessingRow, $newProcessingId, $oldActionData['related_id']);

                $newActionData = array(
                    'processing_id'             => $newProcessingId,
                    'account_id'                => $oldActionData['account_id'],
                    'request_pending_single_id' => $oldActionData['request_pending_single_id'],
                    'related_id'                => $listingProductId,
                    'type'                      => $oldActionData['action_type'],
                    'request_data'              => $oldActionData['input_data'],
                    'start_date'                => $oldActionData['create_date'],
                    'update_date'               => $oldActionData['update_date'],
                    'create_date'               => $oldActionData['create_date'],
                );

                $this->getConnection()->insert($this->getTableName('amazon_processing_action'), $newActionData);

                if (!empty($oldActionData['is_skipped'])) {
                    $this->getConnection()->update(
                        $this->getTableName('listing_product'),
                        array('need_synch_rules_check' => 1),
                        array('id = ?' => $listingProductId)
                    );
                }
            }
        }

        $this->getConnection()->delete(
            $this->getTableName('processing_lock'),
            array('processing_id = ?' => $oldProcessingRow['id'])
        );
    }

    private function processAmazonOrderProcessing(array $oldProcessingRow)
    {
        if (!empty($oldProcessingRow['is_completed'])) {
            $oldProcessingParams = json_decode($oldProcessingRow['params'], true);
            if (isset($oldProcessingParams['request_data']['items'])) {
                $changesIds = array_keys($oldProcessingParams['request_data']['items']);
            } else {
                $changesIds = array_keys($oldProcessingParams['request_data']['orders']);
            }

            foreach ($changesIds as $changeId) {
                $newProcessingData = $this->prepareAmazonOrderProcessingData($oldProcessingRow, $changeId);

                $this->getConnection()->insert($this->getTableName('processing'), $newProcessingData);

                $newProcessingParams = json_decode($newProcessingData['params'], true);
                $this->updateProcessingLocks(
                    $oldProcessingRow, $this->getConnection()->lastInsertId(), $newProcessingParams['order_id']
                );
            }
        } else {
            $processingActionBackupTable = $this->getBackupTableName('amazon_processing_action');
            $processingActionItemBackupTable = $this->getBackupTableName('amazon_processing_action_item');

            $oldActionsData = $this->getConnection()->query("
SELECT `apab`.`account_id` AS `account_id`,
       `apab`.`type` AS `action_type`,
       `apab`.`update_date` AS `update_date`,
       `apab`.`create_date` AS `create_date`,
       `apaib`.`request_pending_single_id` AS `request_pending_single_id`,
       `apaib`.`related_id` AS `related_id`,
       `apaib`.`input_data` AS `input_data`,
       `apaib`.`output_data` AS `output_data`,
       `apaib`.`output_messages` AS `output_messages`,
       `apaib`.`is_skipped` AS `is_skipped`
FROM `{$processingActionItemBackupTable}` AS `apaib`
LEFT JOIN `{$processingActionBackupTable}` AS `apab` ON `apab`.`id` = `apaib`.`action_id`
WHERE `apab`.`processing_id` = {$oldProcessingRow['id']}
            ")->fetchAll(\PDO::FETCH_ASSOC);

            foreach ($oldActionsData as $oldActionData) {
                $newProcessingData = $this->prepareAmazonOrderProcessingData(
                    $oldProcessingRow, $oldActionData['related_id']
                );

                if (is_null($newProcessingData['result_data']) &&
                    !empty($oldActionData['is_completed']) &&
                    !empty($oldActionData['output_messages'])
                ) {
                    $newProcessingData['result_data'] = array(
                        'messages' => json_decode($oldActionData['output_messages'], true),
                    );

                    if (!empty($oldActionData['output_data'])) {
                        $newProcessingData['result_data'] = array_merge(
                            json_decode($oldActionData['output_data'], true), $newProcessingData['result_data']
                        );
                    }
                }

                $this->getConnection()->insert($this->getTableName('processing'), $newProcessingData);

                $newProcessingId = $this->getConnection()->lastInsertId();

                $newProcessingParams = json_decode($newProcessingData['params'], true);
                $this->updateProcessingLocks($oldProcessingRow, $newProcessingId, $newProcessingParams['order_id']);

                $newActionData = array(
                    'processing_id'             => $newProcessingId,
                    'account_id'                => $oldActionData['account_id'],
                    'request_pending_single_id' => $oldActionData['request_pending_single_id'],
                    'related_id'                => $oldActionData['related_id'],
                    'type'                      => $oldActionData['action_type'],
                    'request_data'              => $oldActionData['input_data'],
                    'start_date'                => $oldActionData['create_date'],
                    'update_date'               => $oldActionData['update_date'],
                    'create_date'               => $oldActionData['create_date'],
                );

                $this->getConnection()->insert($this->getTableName('amazon_processing_action'), $newActionData);
            }
        }

        $this->getConnection()->delete(
            $this->getTableName('processing_lock'),
            array('processing_id = ?' => $oldProcessingRow['id'])
        );
    }

    //########################################

    private function prepareEbayItemProcessingData(array $oldProcessingRow, $listingProductId)
    {
        $isMultiple = strpos($oldProcessingRow['model'], 'Multiple') !== false;

        $oldProcessingParams = json_decode($oldProcessingRow['params'], true);
        $oldResponserParams  = $oldProcessingParams['responser_params'];

        if (!$isMultiple) {
            $productResponserData = $oldResponserParams['product'];
        } else {
            $productResponserData = array_merge(
                array('id' => $listingProductId),
                $oldResponserParams['products'][$listingProductId]
            );
        }

        $newResponserParams = array(
            'is_realtime'     => $oldResponserParams['is_realtime'],
            'account_id'      => $oldResponserParams['account_id'],
            'action_type'     => $oldResponserParams['action_type'],
            'lock_identifier' => $oldResponserParams['lock_identifier'],
            'logs_action'     => $oldResponserParams['logs_action'],
            'logs_action_id'  => $oldResponserParams['logs_action_id'],
            'status_changer'  => $oldResponserParams['status_changer'],
            'params'          => $oldResponserParams['params'],
            'product'         => $productResponserData,
        );

        if (!$isMultiple) {
            $processingRequestData = $oldProcessingParams['request_data'];
            $responserModelName    = str_replace('Single', '', $oldProcessingParams['responser_model_name']);
        } else {
            $processingRequestData = $oldProcessingParams['request_data']['items'][$listingProductId];
            $responserModelName    = str_replace('Multiple', '', $oldProcessingParams['responser_model_name']);
        }

        $newProcessingParams = array(
            'component'            => $oldProcessingParams['component'],
            'server_hash'          => $oldProcessingParams['server_hash'],
            'account_id'           => $oldProcessingParams['account_id'],
            'marketplace_id'       => $oldProcessingParams['marketplace_id'],
            'request_data'         => $processingRequestData,
            'listing_product_id'   => $listingProductId,
            'lock_identifier'      => $oldProcessingParams['lock_identifier'],
            'action_type'          => $oldProcessingParams['action_type'],
            'start_date'           => $oldProcessingRow['create_date'],
            'responser_model_name' => $responserModelName,
            'responser_params'     => $newResponserParams,
        );

        $newProcessingResultData = NULL;
        if (!empty($oldProcessingRow['result_data'])) {
            $newProcessingResultData = json_decode($oldProcessingRow['result_data'], true);
            if ($isMultiple) {
                $newProcessingResultData = $newProcessingResultData['result'][$listingProductId];
            }
        }

        return array(
            'model'           => str_replace(
                array('Single\\', 'Multiple\\'), array('', ''), $oldProcessingRow['model']
            ),
            'params'          => json_encode($newProcessingParams),
            'is_completed'    => $oldProcessingRow['is_completed'],
            'result_data'     => !is_null($newProcessingResultData) ? json_encode($newProcessingResultData) : NULL,
            'expiration_date' => $oldProcessingRow['expiration_date'],
            'update_date'     => $oldProcessingRow['update_date'],
            'create_date'     => $oldProcessingRow['create_date'],
        );
    }

    private function prepareAmazonProductProcessingData(array $oldProcessingRow, $listingProductId)
    {
        $oldProcessingParams = json_decode($oldProcessingRow['params'], true);
        $oldResponserParams  = $oldProcessingParams['responser_params'];

        $newResponserParams = array(
            'account_id'      => $oldResponserParams['account_id'],
            'action_type'     => $oldResponserParams['action_type'],
            'lock_identifier' => $oldResponserParams['lock_identifier'],
            'logs_action'     => $oldResponserParams['logs_action'],
            'logs_action_id'  => $oldResponserParams['logs_action_id'],
            'status_changer'  => $oldResponserParams['status_changer'],
            'params'          => $oldResponserParams['params'],
            'product'         => array_merge(
                array('id' => $listingProductId),
                $oldResponserParams['products'][$listingProductId]
            ),
        );

        $newProcessingParams = array(
            'component'            => $oldProcessingParams['component'],
            'server_hash'          => $oldProcessingParams['server_hash'],
            'account_id'           => $oldProcessingParams['account_id'],
            'request_data'         => $oldProcessingParams['request_data']['items'][$listingProductId],
            'listing_product_id'   => $listingProductId,
            'lock_identifier'      => $oldProcessingParams['lock_identifier'],
            'action_type'          => $oldProcessingParams['action_type'],
            'start_date'           => $oldProcessingRow['create_date'],
            'responser_model_name' => str_replace('Multiple', '', $oldProcessingParams['responser_model_name']),
            'responser_params'     => $newResponserParams,
        );

        $newProcessingResultData = NULL;
        if (!empty($oldProcessingRow['result_data'])) {
            $oldProcessingResultData = json_decode($oldProcessingRow['result_data'], true);

            $newProcessingResultData = array(
                'messages' => $oldProcessingResultData['messages'][$listingProductId],
            );

            if (isset($oldProcessingResultData['asins'][$listingProductId])) {
                $newProcessingResultData['asins'] = $oldProcessingResultData['asins'][$listingProductId];
            }
        }

        return array(
            'model'           => str_replace('Multiple', '', $oldProcessingRow['model']),
            'params'          => json_encode($newProcessingParams),
            'result_data'     => !is_null($newProcessingResultData) ? json_encode($newProcessingResultData) : NULL,
            'is_completed'    => $oldProcessingRow['is_completed'],
            'expiration_date' => $oldProcessingRow['expiration_date'],
            'update_date'     => $oldProcessingRow['update_date'],
            'create_date'     => $oldProcessingRow['create_date'],
        );
    }

    private function prepareAmazonOrderProcessingData(array $oldProcessingRow, $changeId)
    {
        $oldProcessingParams = json_decode($oldProcessingRow['params'], true);
        $oldResponserParams  = $oldProcessingParams['responser_params'];

        $orderId = NULL;
        foreach ($oldResponserParams as $responserParamsChangeId => $orderResponserParams) {
            if ($responserParamsChangeId != $changeId) {
                continue;
            }

            $orderId = $orderResponserParams['order_id'];
            break;
        }

        $newResponserParams = array(
            'order' => $oldResponserParams[$changeId],
        );

        $newProcessingParams = array(
            'component'            => $oldProcessingParams['component'],
            'server_hash'          => $oldProcessingParams['server_hash'],
            'account_id'           => $oldProcessingParams['account_id'],
            'request_data'         => isset($oldProcessingParams['request_data']['items'])
                ? $oldProcessingParams['request_data']['items'][$changeId]
                : $oldProcessingParams['request_data']['orders'][$changeId],
            'order_id'             => $orderId,
            'responser_model_name' => $oldProcessingParams['responser_model_name'],
            'responser_params'     => $newResponserParams,
        );

        $newProcessingResultData = NULL;

        if (!empty($oldProcessingRow['result_data'])) {
            $oldProcessingResultData = json_decode($oldProcessingRow['result_data'], true);

            $newProcessingResultData = array(
                'messages' => $oldProcessingResultData['messages'][$changeId],
            );
        }

        return array(
            'model'           => $oldProcessingRow['model'],
            'params'          => json_encode($newProcessingParams),
            'result_data'     => !is_null($newProcessingResultData) ? json_encode($newProcessingResultData) : NULL,
            'is_completed'    => $oldProcessingRow['is_completed'],
            'expiration_date' => $oldProcessingRow['expiration_date'],
            'update_date'     => $oldProcessingRow['update_date'],
            'create_date'     => $oldProcessingRow['create_date'],
        );
    }

    //########################################

    private function updateProcessingLocks(array $oldProcessingRow, $newProcessingId, $objectId  = NULL)
    {
        $where = array(
            'processing_id = ?' => $oldProcessingRow['id'],
        );

        if (!is_null($objectId)) {
            $where['object_id = ?'] = $objectId;
        }

        $this->getConnection()->update(
            $this->getTableName('processing_lock'),
            array('processing_id' => $newProcessingId),
            $where
        );
    }

    //########################################

    private function getTableName($tableName)
    {
        return $this->helperFactory->getObject('Module\Database\Tables')->getFullName($tableName);
    }

    private function getBackupTableName($tableName)
    {
        $tableName = $this->getTableName($tableName).self::BACKUP_TABLE_SUFFIX;

        if (strlen($tableName) > self::BACKUP_TABLE_IDENTIFIER_MAX_LEN) {
            $tableName = 'm2epro'.'_'.sha1($tableName).self::BACKUP_TABLE_SUFFIX;
        }

        return $tableName;
    }

    //########################################

    /**
     * @param $tableName
     * @return \Ess\M2ePro\Model\Setup\Database\Modifier\Table
     */
    protected function getTableModifier($tableName)
    {
        return $this->modelFactory->getObject('Setup\Database\Modifier\Table',
            [
                'installer' => $this->installer,
                'tableName' => $tableName,
            ]
        );
    }

    /**
     * @param $configName
     * @return \Ess\M2ePro\Model\Setup\Database\Modifier\Config
     */
    protected function getConfigModifier($configName)
    {
        $tableName = $configName.'_config';

        return $this->modelFactory->getObject('Setup\Database\Modifier\Config',
            [
                'installer' => $this->installer,
                'tableName' => $tableName,
            ]
        );
    }

    //########################################
}
<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2016 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\UpgradeData\v1_1_0__v1_2_0;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

class LogsImprovements extends AbstractFeature
{
    const LIMIT_LOGS_COUNT = 100000;

    const TASK_DELETE            = 'delete';
    const TASK_ACTION_ID         = 'action_id';
    const TASK_MODIFY_ACTION_ID  = 'modify_action_id';
    const TASK_MODIFY_ENTITY_ID  = 'modify_entity_id';
    const TASK_INDEX             = 'index';
    const TASK_COLUMNS           = 'columns';

    //########################################

    public function getBackupTables()
    {
        return ['module_config'];
    }

    public function execute()
    {
        foreach ($this->getProcessSubjects() as $subject) {
            foreach ($subject['tasks'] as $task) {
                $this->processTask($task, $subject['params']);
            }
        }
    }

    //########################################

    protected function getProcessSubjects()
    {
        return [
            [
                'params' => [
                    'table' => 'listing_log',
                    'config' => '/logs/listings/',
                    'entity_table' => 'listing',
                    'entity_id_field' => 'listing_id'
                ],
                'tasks' => [
                    self::TASK_DELETE,
                    self::TASK_ACTION_ID,
                    self::TASK_MODIFY_ACTION_ID,
                    self::TASK_MODIFY_ENTITY_ID,
                    self::TASK_INDEX,
                    self::TASK_COLUMNS
                ]
            ],
            [
                'params' => [
                    'table' => 'listing_other_log',
                    'config' => '/logs/other_listings/',
                    'entity_table' => 'listing_other',
                    'entity_id_field' => 'listing_other_id'
                ],
                'tasks' => [
                    self::TASK_DELETE,
                    self::TASK_ACTION_ID,
                    self::TASK_MODIFY_ACTION_ID,
                    self::TASK_MODIFY_ENTITY_ID,
                    self::TASK_INDEX,
                    self::TASK_COLUMNS
                ]
            ],
            [
                'params' => [
                    'table' => 'ebay_account_pickup_store_log',
                    'config' => '/logs/ebay_pickup_store/',
                ],
                'tasks' => [
                    self::TASK_DELETE,
                    self::TASK_ACTION_ID,
                    self::TASK_MODIFY_ACTION_ID,
                    self::TASK_INDEX
                ]
            ],
            [
                'params' => [
                    'table' => 'order_log',
                    'entity_table' => 'order',
                    'entity_id_field' => 'order_id'
                ],
                'tasks' => [
                    self::TASK_DELETE,
                    self::TASK_MODIFY_ENTITY_ID,
                    self::TASK_INDEX,
                    self::TASK_COLUMNS
                ]
            ],
            [
                'params' => [
                    'table' => 'synchronization_log',
                ],
                'tasks' => [
                    self::TASK_DELETE,
                    self::TASK_INDEX
                ]
            ]
        ];
    }

    protected function processTask($task, $params)
    {
        switch ($task) {
            case self::TASK_DELETE:
                $this->processDelete($params['table']);
                break;
            case self::TASK_ACTION_ID:
                $this->processActionId($params['table'], $params['config']);
                break;
            case self::TASK_MODIFY_ACTION_ID:
                $this->processModifyActionId($params['table']);
                break;
            case self::TASK_MODIFY_ENTITY_ID:
                $this->processModifyEntityId($params['table'], $params['entity_id_field']);
                break;
            case self::TASK_INDEX:
                $this->processIndex($params['table']);
                break;
            case self::TASK_COLUMNS:
                $this->processColumns($params['table'], $params['entity_table'], $params['entity_id_field']);
                break;
        }
    }

    //----------------------------------------

    protected function processDelete($tableName)
    {
        $table = $this->getFullTableName($tableName);

        $select = $this->getConnection()->select()->from(
            $table,
            [new \Zend_Db_Expr('COUNT(*)')]
        );

        $logsCount = $this->getConnection()->fetchOne($select);

        if ($logsCount <= self::LIMIT_LOGS_COUNT) {
            return;
        }

        $limit = self::LIMIT_LOGS_COUNT;

        $this->getConnection()->exec("CREATE TABLE `{$table}_temp` LIKE `{$table}`");
        $this->getConnection()->exec("INSERT INTO `{$table}_temp` (
                                        SELECT * FROM `{$table}` ORDER BY `id` DESC LIMIT {$limit}
                                     )");
        $this->getConnection()->exec("DROP TABLE `{$table}`");
        $this->getConnection()->exec("RENAME TABLE `{$table}_temp` TO `{$table}`");
    }

    protected function processModifyActionId($tableName)
    {
        $this->getTableModifier($tableName)->changeColumn('action_id', 'INT(10) UNSIGNED NOT NULL');
    }

    protected function processModifyEntityId($tableName, $entityIdField)
    {
        $this->getTableModifier($tableName)->changeColumn($entityIdField, 'INT(10) UNSIGNED NOT NULL');
    }

    protected function processIndex($tableName)
    {
        $this->getTableModifier($tableName)->addIndex('create_date');
    }

    protected function processColumns($tableName, $entityTableName, $entityIdField)
    {
        $this->getTableModifier($tableName)
            ->addColumn('account_id', 'INT(10) UNSIGNED NOT NULL', NULL, 'id', true, false)
            ->addColumn('marketplace_id', 'INT(10) UNSIGNED NOT NULL', NULL, 'account_id', true, false)
            ->commit();

        $table = $this->getFullTableName($tableName);
        $entityTable = $this->getFullTableName($entityTableName);

        $this->getConnection()->exec(<<<SQL
UPDATE `{$table}` `log_table`
  INNER JOIN `{$entityTable}` `entity_table` ON `log_table`.`{$entityIdField}` = `entity_table`.`id`
SET
  `log_table`.`account_id` = `entity_table`.`account_id`,
  `log_table`.`marketplace_id` = `entity_table`.`marketplace_id`;
SQL
        );

        $this->getConnection()->delete($table, [
            'account_id = ?' => 0,
            'marketplace_id = ?' => 0
        ]);
    }

    protected function processActionId($tableName, $configName)
    {
        $noActionIdCondition = new \Zend_Db_Expr('(`action_id` IS NULL) OR (`action_id` = 0)');

        $select = $this->getConnection()->select()
            ->from(
                $this->getFullTableName($tableName),
                [new \Zend_Db_Expr('MIN(`id`)')]
            )
            ->where($noActionIdCondition);

        $minLogIdWithNoActionId = $this->getConnection()->fetchOne($select);

        if (is_null($minLogIdWithNoActionId)) {
            return;
        }

        $nextActionId = $this->getLastActionId($configName);

        $this->getConnection()->update(
            $this->getFullTableName($tableName),
            [
                'action_id' => new \Zend_Db_Expr("`id` - {$minLogIdWithNoActionId} + {$nextActionId}")
            ],
            $noActionIdCondition
        );

        $this->updateLastActionId($tableName, $configName);
    }

    //----------------------------------------

    protected function getLastActionId($configName)
    {
        $config = $this->getConfigModifier('module')->getEntity(
            $configName, 'last_action_id'
        );

        return $config->getValue() + 100;
    }

    protected function updateLastActionId($tableName, $configName)
    {
        $select = $this->getConnection()->select()->from(
            $this->getFullTableName($tableName),
            [new \Zend_Db_Expr('MAX(`action_id`)')]
        );

        $maxActionId = $this->getConnection()->fetchOne($select);

        $config = $this->getConfigModifier('module')->getEntity(
            $configName, 'last_action_id'
        );

        $config->updateValue($maxActionId + 100);
    }

    //########################################
}
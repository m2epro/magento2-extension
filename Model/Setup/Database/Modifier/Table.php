<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Setup\Database\Modifier;

use Ess\M2ePro\Model\Exception\Setup;
use \Magento\Framework\DB\Ddl\Table as DdlTable;

/**
 * Class \Ess\M2ePro\Model\Setup\Database\Modifier\Table
 */
class Table extends AbstractModifier
{
    const COMMIT_KEY_ADD_COLUMN    = 'add_column';
    const COMMIT_KEY_DROP_COLUMN   = 'drop_column';
    const COMMIT_KEY_CHANGE_COLUMN = 'change_column';
    const COMMIT_KEY_ADD_INDEX     = 'add_index';
    const COMMIT_KEY_DROP_INDEX    = 'drop_index';

    protected $sqlForCommit = [];
    protected $columnsForCheckBeforeCommit = [];

    //########################################

    public function truncate()
    {
        $this->connection->truncateTable($this->tableName);
        return $this;
    }

    //########################################

    /**
     * @param string $name
     * @return bool
     */
    public function isColumnExists($name)
    {
        return $this->connection->tableColumnExists($this->tableName, $name);
    }

    /**
     * @param string $from
     * @param string $to
     * @param bool $renameIndex
     * @param bool $autoCommit
     * @return $this
     * @throws Setup
     */
    public function renameColumn($from, $to, $renameIndex = true, $autoCommit = true)
    {
        if (!$this->isColumnExists($from) && $this->isColumnExists($to)) {
            return $this;
        }

        if ($this->isColumnExists($from) && $this->isColumnExists($to)) {
            throw new Setup(
                "Column '{$from}' cannot be changed to '{$to}', because last one
                 already exists in '{$this->tableName}' table."
            );
        }

        if (!$this->isColumnExists($from) && !$this->isColumnExists($to)) {
            throw new Setup(
                "Column '{$from}' cannot be changed, because
                 does not exist in '{$this->tableName}' table."
            );
        }

        $definition = $this->buildColumnDefinitionByName($from, $autoCommit);

        if (empty($definition)) {
            throw new Setup(
                "Definition for column '{$from}' in '{$this->tableName}' table is empty."
            );
        }

        if ($autoCommit) {
            $this->connection->changeColumn($this->tableName, $from, $to, $definition);
        } else {
            $this->addQueryToCommit(
                self::COMMIT_KEY_CHANGE_COLUMN,
                'CHANGE COLUMN %s %s %s',
                [$from, $to],
                $definition
            );
        }

        if ($renameIndex) {
            $this->renameIndex($from, $to, $autoCommit);
        }

        return $this;
    }

    // ---------------------------------------

    /**
     * @param string $name
     * @param string $type
     * @param string|null $default
     * @param string|null $after
     * @param bool $addIndex
     * @param bool $autoCommit
     * @return $this
     * @throws Setup
     */
    public function addColumn($name, $type, $default = null, $after = null, $addIndex = false, $autoCommit = true)
    {
        if ($this->isColumnExists($name)) {
            return $this;
        }

        $definition = $this->buildColumnDefinition($type, $default, $after, $autoCommit);

        if (empty($definition)) {
            throw new Setup(
                "Definition for '{$this->tableName}'.'{$name}' column is empty."
            );
        }

        if ($autoCommit) {
            $this->connection->addColumn($this->tableName, $name, $definition);
        } else {
            $this->addQueryToCommit(
                self::COMMIT_KEY_ADD_COLUMN,
                'ADD COLUMN %s %s',
                [$name],
                $definition
            );
        }

        $addIndex && $this->addIndex($name, $autoCommit);

        return $this;
    }

    /**
     * @param string $name
     * @param string $type
     * @param string|null $default
     * @param string|null $after
     * @param bool $autoCommit
     * @return $this
     * @throws Setup
     */
    public function changeColumn($name, $type, $default = null, $after = null, $autoCommit = true)
    {
        if (!$this->isColumnExists($name)) {
            throw new Setup(
                "Column '{$name}' does not exist in '{$this->tableName}' table."
            );
        }

        $definition = $this->buildColumnDefinition($type, $default, $after, $autoCommit);

        if (empty($definition)) {
            throw new Setup(
                "Definition for '{$this->tableName}'.'{$name}' column is empty."
            );
        }

        if ($autoCommit) {
            $this->connection->modifyColumn($this->tableName, $name, $definition);
        } else {
            $this->addQueryToCommit(
                self::COMMIT_KEY_CHANGE_COLUMN,
                'MODIFY COLUMN %s %s',
                [$name],
                $definition
            );
        }

        return $this;
    }

    /**
     * @param string $name
     * @param bool $dropIndex
     * @param bool $autoCommit
     * @return $this
     * @throws Setup
     */
    public function dropColumn($name, $dropIndex = true, $autoCommit = true)
    {
        if (!$this->isColumnExists($name)) {
            return $this;
        }

        if ($autoCommit) {
            $this->connection->dropColumn($this->tableName, $name);
        } else {
            $this->addQueryToCommit(self::COMMIT_KEY_DROP_COLUMN, 'DROP COLUMN %s', [$name]);
        }

        $dropIndex && $this->dropIndex($name, $autoCommit);

        return $this;
    }

    //########################################

    /**
     * @param string $name
     * @return bool
     * @throws Setup
     */
    public function isIndexExists($name)
    {
        $indexList = $this->connection->getIndexList($this->tableName);
        return isset($indexList[strtoupper($name)]);
    }

    /**
     * @param string $from
     * @param string $to
     * @param bool $autoCommit
     * @return $this
     */
    public function renameIndex($from, $to, $autoCommit = true)
    {
        if (!$this->isIndexExists($from)) {
            return $this;
        }

        return $this->dropIndex($from, $autoCommit)->addIndex($to, $autoCommit);
    }

    // ---------------------------------------

    /**
     * @param string $name
     * @param bool $autoCommit
     * @return $this
     * @throws Setup
     */
    public function addIndex($name, $autoCommit = true)
    {
        if ($this->isIndexExists($name)) {
            return $this;
        }

        if ($autoCommit) {
            $this->connection->addIndex($this->tableName, $name, $name);
        } else {
            $this->addQueryToCommit(self::COMMIT_KEY_ADD_INDEX, 'ADD INDEX %s (%s)', [$name, $name]);
        }

        return $this;
    }

    /**
     * @param string $name
     * @param bool $autoCommit
     * @return $this
     * @throws Setup
     */
    public function dropIndex($name, $autoCommit = true)
    {
        if (!$this->isIndexExists($name)) {
            return $this;
        }

        if ($autoCommit) {
            $this->connection->dropIndex($this->tableName, $name);
        } else {
            $this->addQueryToCommit(self::COMMIT_KEY_DROP_INDEX, 'DROP KEY %s', [$name]);
        }

        return $this;
    }

    //########################################

    private function buildColumnDefinition($type, $default = null, $after = null, $autoCommit = true)
    {
        if ($autoCommit) {
            $pattern = "#^(?P<type>[a-z]+(?:\(\d+\))?)";
            $pattern .= "(?:(?P<unsigned>\sUNSIGNED)?(?P<nullable>\s(?:NOT\s)?NULL)?)?#i";

            $matches = [];
            $definitionData = ['type' => $type];

            if (preg_match($pattern, $type, $matches) !== false && isset($matches['type'])) {
                $typeMap = [
                    DdlTable::TYPE_SMALLINT => ['TINYINT', 'SMALLINT'],
                    DdlTable::TYPE_INTEGER  => ['INT'],
                    DdlTable::TYPE_FLOAT    => ['FLOAT'],
                    DdlTable::TYPE_DECIMAL  => ['DECIMAL'],
                    DdlTable::TYPE_DATETIME => ['DATETIME'],
                    DdlTable::TYPE_TEXT     => ['VARCHAR', 'TEXT', 'LONGTEXT'],
                    DdlTable::TYPE_BLOB     => ['BLOB', 'LONGBLOB'],
                ];

                $size = null;
                $type = $matches['type'];
                if (strpos($type, '(') !== false) {
                    $size = str_replace(['(', ')'], '', substr($type, strpos($type, '(')));
                    $type = substr($type, 0, strpos($type, '('));
                }

                foreach ($typeMap as $ddlType => $types) {
                    if (!in_array(strtoupper($type), $types)) {
                        continue;
                    }

                    if ($ddlType == DdlTable::TYPE_TEXT || $ddlType == DdlTable::TYPE_BLOB) {
                        $definitionData['length'] = $size;
                    }

                    if ($ddlType == DdlTable::TYPE_DECIMAL && strpos($size, ',') !== false) {
                        list($precision, $scale) = array_map('trim', explode(',', $size, 2));
                        $definitionData['precision'] = (int)$precision;
                        $definitionData['scale'] = (int)$scale;
                    }

                    $definitionData['type'] = $ddlType;
                    break;
                }

                if (!empty($matches['unsigned'])) {
                    $definitionData['unsigned'] = true;
                }

                if (!empty($matches['nullable'])) {
                    $definitionData['nullable'] = strpos(strtolower($matches['nullable']), 'not null') ==! false  ?
                        false : true;
                }
            }

            if ($default !== null) {
                $definitionData['default'] = $default === 'NULL' ? null : $default;
            }

            if ($after !== null) {
                if (!$this->isColumnExists($after)) {
                    throw new Setup(
                        "After column '{$after}' does not exist in '{$this->tableName}' table."
                    );
                }

                $definitionData['after'] = $after;
            }

            $definitionData['comment'] = 'field';

            return $definitionData;
        }

        $definition = $type;

        if ($default !== null) {
            if ($default === 'NULL') {
                $definition .= ' DEFAULT NULL';
            } else {
                $definition .= ' DEFAULT ' . $this->connection->quote($default);
            }
        }

        if (!empty($after)) {
            $this->columnsForCheckBeforeCommit[] = $after;
            $definition .= ' AFTER ' . $this->connection->quoteIdentifier($after);
        }

        return $definition;
    }

    private function buildColumnDefinitionByName($name, $autoCommit = false)
    {
        if (!$this->isColumnExists($name)) {
            throw new Setup(
                "Base column '{$name}' does not exist in '{$this->tableName}' table."
            );
        }

        $tableColumns = $this->connection->describeTable($this->tableName);

        if (!isset($tableColumns[$name])) {
            throw new Setup(
                "Describe for column '{$name}' does not exist in '{$this->tableName}' table."
            );
        }

        $columnInfo = $tableColumns[$name];

        if ($autoCommit) {
            return $this->connection->getColumnCreateByDescribe($columnInfo);
        }

        $type = $columnInfo['DATA_TYPE'];
        if (is_numeric($columnInfo['LENGTH']) && $columnInfo['LENGTH'] > 0) {
            $type .= '('.$columnInfo['LENGTH'].')';
        } elseif (is_numeric($columnInfo['PRECISION']) && is_numeric($columnInfo['SCALE'])) {
            $type .= sprintf('(%d,%d)', $columnInfo['PRECISION'], $columnInfo['SCALE']);
        }

        $default = '';
        if ($columnInfo['DEFAULT'] !== false) {
            $this->connection->quoteInto('DEFAULT ?', $columnInfo['DEFAULT']);
        }

        return sprintf(
            '%s %s %s %s %s',
            $type,
            $columnInfo['UNSIGNED'] ? 'UNSIGNED' : '',
            $columnInfo['NULLABLE'] ? 'NULL' : 'NOT NULL',
            $default,
            $columnInfo['IDENTITY'] ? 'AUTO_INCREMENT' : ''
        );
    }

    //########################################

    private function addQueryToCommit($key, $queryPattern, array $columns, $definition = null)
    {
        foreach ($columns as &$column) {
            $column = $this->connection->quoteIdentifier($column);
        }

        $queryArgs = $definition !== null ? array_merge($columns, [$definition]) : $columns;
        $tempQuery = vsprintf($queryPattern, $queryArgs);

        if (isset($this->sqlForCommit[$key]) && in_array($tempQuery, $this->sqlForCommit[$key])) {
            return $this;
        }

        $this->sqlForCommit[$key][] = $tempQuery;
        return $this;
    }

    private function checkColumnsBeforeCommit()
    {
        foreach ($this->columnsForCheckBeforeCommit as $index => $columnForCheck) {
            if ($this->isColumnExists($columnForCheck)) {
                unset($this->columnsForCheckBeforeCommit[$index]);
                continue;
            }

            foreach ($this->sqlForCommit as $key => $sqlData) {
                if (!is_array($sqlData) || in_array($key, [self::COMMIT_KEY_ADD_INDEX,
                                                                self::COMMIT_KEY_DROP_INDEX,
                                                                self::COMMIT_KEY_DROP_COLUMN])
                ) {
                    continue;
                }

                $pattern = '/COLUMN\s(`'.$columnForCheck.'`|`[^`]+`\s`'.$columnForCheck.'`)/';
                $tempSql = implode(', ', $sqlData);

                if (preg_match($pattern, $tempSql)) {
                    unset($this->columnsForCheckBeforeCommit[$index]);
                    break;
                }
            }
        }

        return empty($this->columnsForCheckBeforeCommit);
    }

    /**
     * @return $this
     * @throws Setup
     */
    public function commit()
    {
        if (empty($this->sqlForCommit)) {
            return $this;
        }

        $order = [
            self::COMMIT_KEY_ADD_COLUMN,
            self::COMMIT_KEY_CHANGE_COLUMN,
            self::COMMIT_KEY_DROP_COLUMN,
            self::COMMIT_KEY_ADD_INDEX,
            self::COMMIT_KEY_DROP_INDEX
        ];

        $tempSql = '';
        $sep = '';

        foreach ($order as $orderKey) {
            foreach ($this->sqlForCommit as $key => $sqlData) {
                if ($orderKey != $key || !is_array($sqlData)) {
                    continue;
                }

                $tempSql .= $sep . implode(', ', $sqlData);
                $sep = ', ';
            }
        }

        $resultSql = sprintf(
            'ALTER TABLE %s %s',
            $this->connection->quoteIdentifier($this->tableName),
            $tempSql
        );

        if (!$this->checkColumnsBeforeCommit()) {
            $this->sqlForCommit = [];
            $failedColumns = implode("', '", $this->columnsForCheckBeforeCommit);

            throw new Setup(
                "Commit for '{$this->tableName}' table is failed
                because '{$failedColumns}' columns does not exist."
            );
        }

        $this->runQuery($resultSql);
        $this->sqlForCommit = [];
        return $this;
    }

    //########################################
}

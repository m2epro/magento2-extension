<?php

namespace Ess\M2ePro\Model\ChangeTracker\Common\QueryBuilder;

class SelectQueryBuilder
{
    private const PART_SELECT = 'select';
    private const PART_FROM = 'from';
    private const PART_JOIN = 'join';
    private const PART_WHERE = 'where';
    private const PART_GROUP = 'group';
    private const PART_DISTINCT = 'distinct';

    private const DEFAULT_PARTS = [
        self::PART_SELECT => [],
        self::PART_FROM => [],
        self::PART_JOIN => [],
        self::PART_WHERE => [],
        self::PART_GROUP => [],
        self::PART_DISTINCT => false,
    ];

    /** @var \Ess\M2ePro\Helper\Module\Database\Structure */
    private $dbHelper;

    /** @var array */
    private $queryParts = self::DEFAULT_PARTS;

    /** @var \Magento\Framework\App\ResourceConnection */
    private $resourceConnection;

    /**
     * @return array
     * @throws \Zend_Db_Statement_Exception
     */
    public function fetchAll(): array
    {
        $stmt = $this->resourceConnection
            ->getConnection()
            ->query($this->getQuery());

        return $stmt->fetchAll();
    }

    /**
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     * @param \Ess\M2ePro\Helper\Module\Database\Structure $dbHelper
     */
    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Helper\Module\Database\Structure $dbHelper
    ) {
        $this->dbHelper = $dbHelper;
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * @return \Ess\M2ePro\Model\ChangeTracker\Common\QueryBuilder\SelectQueryBuilder
     */
    public function makeSubQuery(): SelectQueryBuilder
    {
        $queryBuilder = clone $this;
        $queryBuilder->queryParts = self::DEFAULT_PARTS;

        return $queryBuilder;
    }

    /**
     * @param bool $distinct
     *
     * @return SelectQueryBuilder
     */
    public function distinct(bool $distinct = true): SelectQueryBuilder
    {
        $this->queryParts[self::PART_DISTINCT] = $distinct;

        return $this;
    }

    /**
     * @param string $alias
     * @param string $select
     *
     * @return $this
     */
    public function addSelect(string $alias, string $select): SelectQueryBuilder
    {
        if ($this->isExpressionSelect($select)) {
            $this->queryParts[self::PART_SELECT][$alias] = new \Zend_Db_Expr("( $select )");

            return $this;
        }

        $this->queryParts[self::PART_SELECT][$alias] = $select;

        return $this;
    }

    /**
     * @return array
     */
    public function getSelect(): array
    {
        return $this->queryParts[self::PART_SELECT];
    }

    /**
     * @param string $tableAlias
     * @param mixed $tableName
     *
     * @return $this
     */
    public function from(string $tableAlias, $tableName): SelectQueryBuilder
    {
        $this->queryParts[self::PART_FROM] = [
            $tableAlias => $this->getTableName($tableName),
        ];

        return $this;
    }

    /**
     * @param string $tableAlias
     * @param mixed $tableName
     * @param string $onCondition
     *
     * @return $this
     */
    public function leftJoin(string $tableAlias, $tableName, string $onCondition): SelectQueryBuilder
    {
        $this->queryParts[self::PART_JOIN][$tableAlias] = new QueryBuilderJoin(
            $tableAlias,
            $this->getTableName($tableName),
            $onCondition,
            QueryBuilderJoin::JOIN_LEFT
        );

        return $this;
    }

    /**
     * @param string $tableAlias
     * @param mixed $tableName
     * @param string $onCondition
     *
     * @return $this
     */
    public function innerJoin(string $tableAlias, $tableName, string $onCondition): SelectQueryBuilder
    {
        $this->queryParts[self::PART_JOIN][$tableAlias] = new QueryBuilderJoin(
            $tableAlias,
            $this->getTableName($tableName),
            $onCondition,
            QueryBuilderJoin::JOIN_INNER
        );

        return $this;
    }

    /**
     * @param string $condition
     * @param mixed $params
     *
     * @return SelectQueryBuilder
     */
    public function andWhere(string $condition, $params = null): SelectQueryBuilder
    {
        $this->queryParts[self::PART_WHERE][] = [$condition, $params];

        return $this;
    }

    /**
     * @param string $column
     *
     * @return SelectQueryBuilder
     */
    public function addGroup(string $column): SelectQueryBuilder
    {
        $this->queryParts[self::PART_GROUP][$column] = $column;

        return $this;
    }

    /**
     * @return \Magento\Framework\DB\Select
     */
    public function getQuery(): \Magento\Framework\DB\Select
    {
        $select = $this->resourceConnection
            ->getConnection()
            ->select();

        $select->from($this->queryParts[self::PART_FROM], null);
        $select->columns($this->queryParts[self::PART_SELECT]);
        $select->distinct($this->queryParts[self::PART_DISTINCT]);
        $select->group($this->queryParts[self::PART_GROUP]);

        /** @var QueryBuilderJoin $join */
        foreach ($this->queryParts[self::PART_JOIN] as $join) {
            $join->appendToQuery($select);
        }

        foreach ($this->queryParts[self::PART_WHERE] as $whereArgs) {
            $select->where(...$whereArgs);
        }

        return $select;
    }

    /**
     * @param string|SelectQueryBuilder|\Magento\Framework\DB\Select $table
     *
     * @return string|\Magento\Framework\DB\Select
     */
    private function getTableName($table)
    {
        if ($table instanceof self) {
            return $table->getQuery();
        }

        if ($table instanceof \Magento\Framework\DB\Select) {
            return $table;
        }

        // dont add table prefix to temporary tables
        if (0 === strpos($table, "tmp_select")) {
            return $table;
        }

        return $this->dbHelper->getTableNameWithPrefix($table);
    }

    /**
     * @param string $select
     *
     * @return bool
     */
    private function isExpressionSelect(string $select): bool
    {
        return (bool)preg_match('/^[a-z_\-.]+$/i', $select) === false;
    }
}

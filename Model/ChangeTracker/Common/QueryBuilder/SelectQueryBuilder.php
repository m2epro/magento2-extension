<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\ChangeTracker\Common\QueryBuilder;

class SelectQueryBuilder
{
    private const PART_SELECT = 'select';
    private const PART_FROM = 'from';
    private const PART_JOIN = 'join';
    private const PART_AND_WHERE = 'and_where';
    private const PART_OR_WHERE = 'or_where';
    private const PART_GROUP = 'group';
    private const PART_DISTINCT = 'distinct';

    private const DEFAULT_PARTS = [
        self::PART_SELECT => [],
        self::PART_FROM => [],
        self::PART_JOIN => [],
        self::PART_AND_WHERE => [],
        self::PART_OR_WHERE => [],
        self::PART_GROUP => [],
        self::PART_DISTINCT => false,
    ];

    private \Ess\M2ePro\Helper\Module\Database\Structure $dbHelper;
    private array $queryParts = self::DEFAULT_PARTS;
    private \Magento\Framework\App\ResourceConnection $resourceConnection;

    public function __toString(): string
    {
        return (string)$this->getQuery();
    }

    /**
     * @throws \Zend_Db_Statement_Exception
     */
    public function fetchAll(): array
    {
        $stmt = $this->resourceConnection
            ->getConnection()
            ->query($this->getQuery());

        return $stmt->fetchAll();
    }

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Helper\Module\Database\Structure $dbHelper
    ) {
        $this->dbHelper = $dbHelper;
        $this->resourceConnection = $resourceConnection;
    }

    public function distinct(bool $distinct = true): self
    {
        $this->queryParts[self::PART_DISTINCT] = $distinct;

        return $this;
    }

    public function addSelect(string $alias, string $select): self
    {
        if ($this->isExpressionSelect($select)) {
            $this->queryParts[self::PART_SELECT][$alias] = new \Zend_Db_Expr("( $select )");

            return $this;
        }

        $this->queryParts[self::PART_SELECT][$alias] = $select;

        return $this;
    }

    public function getSelect(): array
    {
        return $this->queryParts[self::PART_SELECT];
    }

    /**
     * @param string|self|\Magento\Framework\DB\Select $tableName
     */
    public function from(string $tableAlias, $tableName): self
    {
        $this->queryParts[self::PART_FROM] = [
            $tableAlias => $this->getTableName($tableName),
        ];

        return $this;
    }

    /**
     * @param string|self|\Magento\Framework\DB\Select $tableName
     */
    public function leftJoin(string $tableAlias, $tableName, string $onCondition): self
    {
        $this->queryParts[self::PART_JOIN][$tableAlias] = new Join(
            $tableAlias,
            $this->getTableName($tableName),
            $onCondition,
            Join::JOIN_LEFT
        );

        return $this;
    }

    /**
     * @param string|self|\Magento\Framework\DB\Select $tableName
     */
    public function innerJoin(string $tableAlias, $tableName, string $onCondition): self
    {
        $this->queryParts[self::PART_JOIN][$tableAlias] = new Join(
            $tableAlias,
            $this->getTableName($tableName),
            $onCondition,
            Join::JOIN_INNER
        );

        return $this;
    }

    /**
     * @param mixed $params
     */
    public function andWhere(string $condition, $params = null): self
    {
        $this->queryParts[self::PART_AND_WHERE][] = [$condition, $params];

        return $this;
    }

    /**
     * @param mixed $params
     */
    public function orWhere(string $condition, $params = null): self
    {
        $this->queryParts[self::PART_OR_WHERE][] = [$condition, $params];

        return $this;
    }

    public function addGroup(string $column): self
    {
        $this->queryParts[self::PART_GROUP][$column] = $column;

        return $this;
    }

    public function getQuery(): \Magento\Framework\DB\Select
    {
        $select = $this->resourceConnection
            ->getConnection()
            ->select();

        $select->from($this->queryParts[self::PART_FROM], null);
        $select->columns($this->queryParts[self::PART_SELECT]);
        $select->distinct($this->queryParts[self::PART_DISTINCT]);
        $select->group($this->queryParts[self::PART_GROUP]);

        /** @var Join $join */
        foreach ($this->queryParts[self::PART_JOIN] as $join) {
            $join->appendToQuery($select);
        }

        foreach ($this->queryParts[self::PART_AND_WHERE] as $whereArgs) {
            $select->where(...$whereArgs);
        }

        foreach ($this->queryParts[self::PART_OR_WHERE] as $whereArgs) {
            $select->orWhere(...$whereArgs);
        }

        return $select;
    }

    /**
     * @param string|self|\Magento\Framework\DB\Select $table
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

    private function isExpressionSelect(string $select): bool
    {
        return (bool)preg_match('/^[a-z_\-.]+$/i', $select) === false;
    }
}

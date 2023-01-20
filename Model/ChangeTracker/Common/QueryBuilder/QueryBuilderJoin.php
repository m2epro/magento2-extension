<?php

namespace Ess\M2ePro\Model\ChangeTracker\Common\QueryBuilder;

class QueryBuilderJoin
{
    public const JOIN_LEFT = 'left';
    public const JOIN_INNER = 'inner';

    /** @var string */
    private $tableAlias;

    /** @var SelectQueryBuilder|string */
    private $tableName;

    /** @var string */
    private $condition;

    /** @var string */
    private $type;

    /**
     * @param string $tableAlias
     * @param SelectQueryBuilder|string $tableName
     * @param string $condition
     * @param string $type
     */
    public function __construct(
        string $tableAlias,
        $tableName,
        string $condition,
        string $type
    ) {
        $this->tableAlias = $tableAlias;
        $this->tableName = $tableName;
        $this->condition = $condition;
        $this->type = $type;
    }

    /**
     * @param \Magento\Framework\DB\Select $query
     *
     * @return \Magento\Framework\DB\Select
     */
    public function appendToQuery(\Magento\Framework\DB\Select $query): \Magento\Framework\DB\Select
    {
        $joinArgs = [
            [$this->tableAlias => $this->tableName],
            $this->condition,
            null,
        ];

        if ($this->type === self::JOIN_LEFT) {
            return $query->joinLeft(...$joinArgs);
        }

        if ($this->type === self::JOIN_INNER) {
            return $query->joinInner(...$joinArgs);
        }

        throw new \RuntimeException('Unknown join type ' . $this->type);
    }
}

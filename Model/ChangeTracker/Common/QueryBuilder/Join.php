<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\ChangeTracker\Common\QueryBuilder;

class Join
{
    public const JOIN_LEFT = 'left';
    public const JOIN_INNER = 'inner';

    private string $tableAlias;
    /** @var SelectQueryBuilder|string */
    private $tableName;
    private string $condition;
    private string $type;

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

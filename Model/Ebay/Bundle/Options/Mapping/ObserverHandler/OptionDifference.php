<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Bundle\Options\Mapping\ObserverHandler;

class OptionDifference
{
    public const TYPE_UPDATED = 'updated';
    public const TYPE_ADDED = 'added';
    public const TYPE_DELETED = 'deleted';

    private string $type;
    private string $oldTitle;
    private string $newTitle;

    public function __construct(string $type, string $oldTitle = '', string $newTitle = '')
    {
        $this->type = $type;
        $this->oldTitle = $oldTitle;
        $this->newTitle = $newTitle;
    }

    public function isUpdated(): bool
    {
        return $this->type === self::TYPE_UPDATED;
    }

    public function getOldTitle(): string
    {
        return $this->oldTitle;
    }

    public function getNewTitle(): string
    {
        return $this->newTitle;
    }
}

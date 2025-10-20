<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\ChangeTracker\Traits;

trait TableNameReplacerTrait
{
    protected function setChannelToTableName(string $tableName): string
    {
        return sprintf($tableName, $this->getChannel());
    }

    abstract protected function getChannel(): string;
}

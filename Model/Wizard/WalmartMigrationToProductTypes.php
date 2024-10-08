<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Wizard;

class WalmartMigrationToProductTypes extends \Ess\M2ePro\Model\Wizard
{
    public const NICK = 'walmartMigrationToProductTypes';

    protected $steps = [
        'readNotification',
    ];

    public function isActive($view): bool
    {
        return true;
    }

    public function getNick(): string
    {
        return self::NICK;
    }
}

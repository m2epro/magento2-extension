<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Wizard;

class AmazonMigrationToProductTypes extends \Ess\M2ePro\Model\Wizard
{
    public const NICK = 'amazonMigrationToProductTypes';

    /**
     * @var string[]
     */
    protected $steps = [
        'readNotification',
    ];

    /**
     * @param mixed $view
     *
     * @return bool
     */
    public function isActive($view): bool
    {
        return true;
    }

    /**
     * @return string
     */
    public function getNick(): string
    {
        return self::NICK;
    }
}

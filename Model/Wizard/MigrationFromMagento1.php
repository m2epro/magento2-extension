<?php

namespace Ess\M2ePro\Model\Wizard;

use Ess\M2ePro\Model\Wizard;

class MigrationFromMagento1 extends Wizard
{
    protected $steps = array(
        'welcome',
        'synchronization',
        'congratulation'
    );

    /**
     * @return string
     */
    public function getNick()
    {
        return 'migrationFromMagento1';
    }
}
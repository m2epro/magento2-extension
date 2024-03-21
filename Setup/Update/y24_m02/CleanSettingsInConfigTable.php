<?php

declare(strict_types=1);

namespace Ess\M2ePro\Setup\Update\y24_m02;

class CleanSettingsInConfigTable extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    /**
     * @throws \Ess\M2ePro\Model\Exception\Setup
     */
    public function execute()
    {
        $this->getConnection()->delete(
            $this->getFullTableName('config'),
            ['`group` LIKE ?' => '/server/location/%']
        );

        $this->getConfigModifier('module')
             ->insert('/server/', 'host', 'https://api.m2epro.com/');
    }
}

<?php

namespace Ess\M2ePro\Setup\Update\y19_m10;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

// @codingStandardsIgnoreFile

class Configs extends AbstractFeature
{
    //########################################

    public function execute()
    {
        $this->getConnection()->update(
            $this->getFullTableName('module_config'),
            ['group' => '/'],
            '`group` IS NULL'
        );
    }

    //########################################
}

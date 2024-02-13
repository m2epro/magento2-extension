<?php

declare(strict_types=1);

namespace Ess\M2ePro\Setup\Update\y24_m02;

class RemoveInstallationKeyFromConfigTable extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    public function execute()
    {
        $this->getConnection()->delete(
            $this->getFullTableName('config'),
            ['`key` = ?' => 'installation_key']
        );
    }
}

<?php

namespace Ess\M2ePro\Setup\Update\y23_m11;

class RemoveSupportUrlFromConfigTable extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    public function execute(): void
    {
        $this->getConnection()->delete(
            $this->getFullTableName('config'),
            [
                '`key` = "support_url"'
            ]
        );
    }
}

<?php

declare(strict_types=1);

namespace Ess\M2ePro\Setup\Update\y24_m10;

class DropTableWalmartDictionarySpecific extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    public function execute()
    {
        $this->getConnection()->dropTable(
            $this->getFullTableName('walmart_dictionary_specific')
        );
    }
}

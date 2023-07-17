<?php

namespace Ess\M2ePro\Setup\Update\y23_m07;

class ChangeDocumentationUrl extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    public function execute(): void
    {
        $this->getConnection()->update(
            $this->getFullTableName('config'),
            ['value' => "https://docs-m2.m2epro.com/"],
            '`key` = "documentation_url"'
        );
    }
}

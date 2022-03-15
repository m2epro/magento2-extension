<?php

namespace Ess\M2ePro\Setup\Update\y22_m02;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

/**
 * Class \Ess\M2ePro\Setup\Update\y22_m02\ChangeDocumentationUrl
 */
class ChangeDocumentationUrl extends AbstractFeature
{
    //########################################

    public function execute()
    {
        $this->getConnection()->update(
            $this->getFullTableName('config'),
            ['value' => "https://m2e.atlassian.net/wiki/"],
            '`key` = "documentation_url"'
        );
    }

    //########################################
}

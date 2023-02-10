<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Update\y23_m01;

class UpdateConfigSupportUrl extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    public function execute()
    {
        $this->getConnection()->update(
            $this->getFullTableName('config'),
            [
                "value" => "https://help.m2epro.com",
            ],
            [
                "`key` = ?" => "support_url"
            ]
        );
    }
}

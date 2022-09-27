<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Update\y22_m09;

class UpdateConfigAttrSupportUrl extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    /**
     * @return void
     * @throws \Ess\M2ePro\Model\Exception\Setup
     */
    public function execute()
    {
        $this->getConnection()->update(
            $this->getFullTableName('config'),
            [
                "value" => "https://m2epro.freshdesk.com",
            ],
            [
                "`key` = ?" => "support_url"
            ]
        );
    }
}

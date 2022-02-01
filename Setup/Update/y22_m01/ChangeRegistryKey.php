<?php

namespace Ess\M2ePro\Setup\Update\y22_m01;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

/**
 * Class \Ess\M2ePro\Setup\Update\y22_m01\ChangeRegistryKey
 */
class ChangeRegistryKey extends AbstractFeature
{
    //########################################

    public function execute()
    {
        $this->getConnection()->update(
            $this->getFullTableName('registry'),
            ['key' => "/registration/user_info/"],
            '`key` = "/wizard/license_form_data/"'
        );
    }

    //########################################
}

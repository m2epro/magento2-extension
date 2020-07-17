<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Update\y20_m06;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

/**
 * Class \Ess\M2ePro\Setup\Update\y20_m06\RemoveCronDomains
 */
class RemoveCronDomains extends AbstractFeature
{
    //########################################

    public function execute()
    {
        $this->installer->getConnection()->delete(
            $this->getFullTableName('config'),
            "`group` = '/cron/service/' AND `key` LIKE 'hostname_%'"
        );
    }

    //########################################
}

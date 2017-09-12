<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2016 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\UpgradeData\v1_3_1__v1_3_2;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

class MovingSynchProductLimitToConfig extends AbstractFeature
{
    //########################################

    public function getBackupTables()
    {
        return ['synchronization_config'];
    }

    public function execute()
    {
        $configModifier = $this->getConfigModifier('synchronization');
        $configModifier->insert(
            '/ebay/templates/synchronization/list/immediately_not_checked/', 'items_limit', '200', NULL
        );
        $configModifier->insert(
            '/ebay/templates/synchronization/revise/total/', 'items_limit', '200', NULL
        );
        $configModifier->insert(
            '/ebay/templates/synchronization/revise/need_synch/', 'items_limit', '200', NULL
        );
        $configModifier->insert(
            '/amazon/templates/synchronization/list/immediately_not_checked/', 'items_limit', '200', NULL
        );
        $configModifier->insert(
            '/amazon/templates/synchronization/revise/total/', 'items_limit', '200', NULL
        );
        $configModifier->insert(
            '/amazon/templates/synchronization/revise/need_synch/', 'items_limit', '200', NULL
        );
    }

    //########################################
}
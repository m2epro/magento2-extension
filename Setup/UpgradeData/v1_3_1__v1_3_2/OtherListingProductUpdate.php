<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2016 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\UpgradeData\v1_3_1__v1_3_2;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

class OtherListingProductUpdate extends AbstractFeature
{
    //########################################

    public function getBackupTables()
    {
        return ['synchronization_config'];
    }

    public function execute()
    {
        $this->getConfigModifier('synchronization')->insert(
            '/ebay/other_listings/update/', 'interval', '3600'
        );
    }

    //########################################
}
<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2016 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\UpgradeData\v1_3_1__v1_3_2;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

class SearchSettingsDataCapacity extends AbstractFeature
{
    //########################################

    public function getBackupTables()
    {
        return ['amazon_listing_product'];
    }

    public function execute()
    {
        $this->getTableModifier('amazon_listing_product')
            ->changeColumn('search_settings_data', 'LONGTEXT', 'NULL');
    }

    //########################################
}
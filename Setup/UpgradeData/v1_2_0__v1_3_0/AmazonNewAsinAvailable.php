<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2016 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\UpgradeData\v1_2_0__v1_3_0;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

class AmazonNewAsinAvailable extends AbstractFeature
{
    //########################################

    public function getBackupTables()
    {
        return ['amazon_marketplace'];
    }

    public function execute()
    {
        $this->getTableModifier('amazon_marketplace')->renameColumn(
            'is_asin_available', 'is_new_asin_available', true
        );
        $this->getTableModifier('amazon_marketplace')->changeColumn(
            'is_new_asin_available', 'SMALLINT(5) UNSIGNED NOT NULL', 1
        );
    }

    //########################################
}
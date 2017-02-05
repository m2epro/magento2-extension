<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2016 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\UpgradeData\v1_3_0__v1_3_1;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

class MarketplacesFeatures extends AbstractFeature
{
    //########################################

    public function getBackupTables()
    {
        return ['ebay_marketplace'];
    }

    public function execute()
    {
        $this->getTableModifier('ebay_marketplace')
            ->addColumn('is_epid', 'SMALLINT(5) UNSIGNED NOT NULL', 0, 'is_holiday_return', true, false)
            ->addColumn('is_ktype', 'SMALLINT(5) UNSIGNED NOT NULL', 0, 'is_epid', true, false)
            ->commit();

        $this->getConnection()->update(
            $this->getFullTableName('ebay_marketplace'),
            ['is_epid' => 1],
            ['marketplace_id = ?' => 9]
        );

        $this->getConnection()->update(
            $this->getFullTableName('ebay_marketplace'),
            ['is_ktype' => 1],
            ['marketplace_id IN (?)' => [3, 4, 7, 8, 10]]
        );
    }

    //########################################
}
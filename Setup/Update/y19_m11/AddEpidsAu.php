<?php

namespace Ess\M2ePro\Setup\Update\y19_m11;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

/**
 * Class \Ess\M2ePro\Setup\Update\y19_m11\RemoveReviseTotal
 */
class AddEpidsAu extends AbstractFeature
{
    //########################################

    public function execute()
    {
        $this->getConfigModifier('module')->insert('/ebay/motors/', 'epids_au_attribute');

        $ebayMarketplace = $this->getFullTableName('ebay_marketplace');
        $this->installer->run(
            "UPDATE `{$ebayMarketplace}` SET `is_epid` = 1 WHERE `marketplace_id` = 4;"
        );

        $this->getTableModifier('ebay_dictionary_motor_epid')
            ->addColumn('street_name', 'VARCHAR(255)', 'NULL', 'submodel', true, false)
            ->commit();
    }

    //########################################
}

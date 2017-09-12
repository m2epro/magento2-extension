<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2016 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\UpgradeData\v1_3_1__v1_3_2;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

class EbayEpidsDeUk extends AbstractFeature
{
    //########################################

    public function getBackupTables()
    {
        return ['ebay_marketplace', 'ebay_listing'];
    }

    public function execute()
    {
        $this->getTableModifier('ebay_dictionary_motor_epid')
             ->addColumn('scope', 'SMALLINT(5) UNSIGNED NOT NULL', '0', 'is_custom', true);

        $this->getConnection()->update(
            $this->getFullTableName('ebay_dictionary_motor_epid'),
            ['scope' => 1]
        );

        $this->getConnection()->update(
            $this->getFullTableName('ebay_marketplace'),
            ['is_epid' => 1],
            ['marketplace_id IN (?)' => [3, 8]]
        );

        $this->getConfigModifier('module')->getEntity('/ebay/motors/', 'epids_uk_attribute')->insert(NULL);
        $this->getConfigModifier('module')->getEntity('/ebay/motors/', 'epids_de_attribute')->insert(NULL);
        $this->getConfigModifier('module')->getEntity('/ebay/motors/', 'epids_attribute')
                                          ->updateKey('epids_motor_attribute');

        $this->getTableModifier('ebay_listing')
             ->addColumn('parts_compatibility_mode', 'VARCHAR(10)', 'NULL', 'product_add_ids');

        $this->getConnection()->exec(<<<SQL
UPDATE `{$this->getFullTableName('ebay_listing')}` mel
INNER JOIN `{$this->getFullTableName('listing')}` ml ON ml.id = mel.listing_id
SET `parts_compatibility_mode` = 'ktypes'
WHERE ml.marketplace_id IN (3, 8);
SQL
        );
    }

    //########################################
}
<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2016 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\UpgradeData\v1_2_0__v1_3_0;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

class EbayItemUUID extends AbstractFeature
{
    //########################################

    public function getBackupTables()
    {
        return ['ebay_listing_product'];
    }

    public function execute()
    {
        $this->getTableModifier('ebay_listing_product')
            ->addColumn('item_uuid', 'VARCHAR(32)', 'NULL', 'ebay_item_id', true, false)
            ->addColumn('is_duplicate', 'SMALLINT(5) UNSIGNED NOT NULL', '0', 'item_uuid', true, false)
            ->commit();
    }

    //########################################
}
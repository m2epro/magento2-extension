<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2016 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\UpgradeData\v1_3_1__v1_3_2;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

class EbayOldItems extends AbstractFeature
{
    //########################################

    public function getBackupTables()
    {
        return [];
    }

    public function execute()
    {
        $this->getTableModifier("ebay_listing_other")
             ->dropColumn("old_items");
    }

    //########################################
}
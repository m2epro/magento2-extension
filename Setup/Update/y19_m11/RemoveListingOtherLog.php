<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Update\y19_m11;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

/**
 * Class \Ess\M2ePro\Setup\Update\y19_m11\RemoveListingOtherLog
 */
class RemoveListingOtherLog extends AbstractFeature
{
    //########################################

    public function execute()
    {
        $listingOtherLogTableName = $this->getFullTableName('listing_other_log');

        if ($this->installer->tableExists($listingOtherLogTableName)) {
            $this->getConnection()->dropTable($listingOtherLogTableName);
        }

        $this->getConfigModifier('module')->delete('/logs/clearing/other_listings/');
        $this->getConfigModifier('module')->delete('/logs/other_listings/');
    }

    //########################################
}

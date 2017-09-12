<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2016 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\UpgradeData\v1_3_1__v1_3_2;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

class UUIDRegenerate extends AbstractFeature
{
    //########################################

    public function getBackupTables()
    {
        return ['ebay_listing_product'];
    }

    public function execute()
    {
        $lpTable = $this->getFullTableName('listing_product');
        $elpTable = $this->getFullTableName('ebay_listing_product');

        // STOPPED,FINISHED,SOLD statuses
        $this->getConnection()->exec(<<<SQL
UPDATE `{$elpTable}` `elp`
INNER JOIN `{$lpTable}` `lp` ON `elp`.`listing_product_id` = `lp`.`id`
SET `elp`.`item_uuid` = NULL
WHERE `lp`.`status` IN (1,3,4)
SQL
        );
    }

    //########################################
}
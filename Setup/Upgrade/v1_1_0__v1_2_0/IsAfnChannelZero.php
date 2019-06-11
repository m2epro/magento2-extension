<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Upgrade\v1_1_0__v1_2_0;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

class IsAfnChannelZero extends AbstractFeature
{
    //########################################

    public function getBackupTables()
    {
        return ['amazon_listing_product'];
    }

    public function execute()
    {
        $this->getTableModifier('amazon_listing_product')
             ->changeColumn('is_afn_channel', 'SMALLINT(4) UNSIGNED NOT NULL', 0, 'online_qty');

        $this->getConnection()->update(
            $this->getFullTableName('amazon_listing_product'),
            ['is_afn_channel' => 0],
            'is_afn_channel IS NULL'
        );
    }

    //########################################
}
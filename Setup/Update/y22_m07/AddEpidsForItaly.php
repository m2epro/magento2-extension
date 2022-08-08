<?php

namespace Ess\M2ePro\Setup\Update\y22_m07;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

class AddEpidsForItaly extends AbstractFeature
{
    public function execute()
    {
        $this->getConfigModifier('module')
             ->insert('/ebay/configuration/', 'it_epids_attribute');

        $ebayMarketplaceTable = $this->getFullTableName('ebay_marketplace');
        $this->installer->run(
            "UPDATE `{$ebayMarketplaceTable}` SET `is_epid` = 1 WHERE `marketplace_id` = 10;"
        );
    }
}

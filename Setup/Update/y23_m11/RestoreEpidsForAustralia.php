<?php

namespace Ess\M2ePro\Setup\Update\y23_m11;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

class RestoreEpidsForAustralia extends AbstractFeature
{
    public function execute()
    {
        $this->addEpidsInConfiguration();
        $this->enableEpidsInMarketplace();
    }

    private function addEpidsInConfiguration(): void
    {
        $this->getConfigModifier('module')
             ->insert('/ebay/configuration/', 'au_epids_attribute', null);

        $this->getConfigModifier('module')
             ->insert('/ebay/configuration/', 'au_epids_visible', 0);
    }

    private function enableEpidsInMarketplace(): void
    {
        $ebayMarketplaceTable = $this->getFullTableName('ebay_marketplace');
        $sql = "UPDATE `{$ebayMarketplaceTable}` SET `is_epid` = 1 WHERE `marketplace_id` = 4;";
        $this->installer->run($sql);
    }
}

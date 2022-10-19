<?php

namespace Ess\M2ePro\Setup\Update\y22_m10;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

class RemoveEpidsForAustralia extends AbstractFeature
{
    /**
     * @return void
     */
    public function execute()
    {
        $this->deleteEpidsFromConfiguration();
        $this->disableEpidsFromMarketplace();
    }

    /**
     * @return void
     */
    private function deleteEpidsFromConfiguration(): void
    {
        $this->getConfigModifier('module')
             ->delete('/ebay/configuration/', 'au_epids_attribute');
    }

    /**
     * @return void
     */
    private function disableEpidsFromMarketplace(): void
    {
        $ebayMarketplaceTable = $this->getFullTableName('ebay_marketplace');
        $sql = "UPDATE `{$ebayMarketplaceTable}` SET `is_epid` = 0 WHERE `marketplace_id` = 4;";
        $this->installer->run($sql);
    }
}

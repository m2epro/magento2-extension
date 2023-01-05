<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Update\y22_m10;

class RemovePickupInStoreTablesAndColumns extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    /**
     * @return void
     * @throws \Ess\M2ePro\Model\Exception\Setup
     */
    public function execute(): void
    {
        $this->getConfigModifier('module')->delete('/logs/clearing/ebay_pickup_store/', 'mode');
        $this->getConfigModifier('module')->delete('/logs/clearing/ebay_pickup_store/', 'days');
        $this->getConfigModifier('module')->delete('/logs/ebay_pickup_store/', 'last_action_id');

        $this->getTableModifier('ebay_marketplace')->dropColumn('is_in_store_pickup');

        $pickupStoreTable = $this->getFullTableName('ebay_account_pickup_store');
        $this->getConnection()->dropTable($pickupStoreTable);

        $pickupStoreStateTable = $this->getFullTableName('ebay_account_pickup_store_state');
        $this->getConnection()->dropTable($pickupStoreStateTable);

        $pickupStoreLogTable = $this->getFullTableName('ebay_account_pickup_store_log');
        $this->getConnection()->dropTable($pickupStoreLogTable);

        $productPickupStoreTable = $this->getFullTableName('ebay_listing_product_pickup_store');
        $this->getConnection()->dropTable($productPickupStoreTable);
    }
}

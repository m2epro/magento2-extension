<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Upgrade\v1_1_0__v1_2_0;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

class InStorePickupGlobalKey extends AbstractFeature
{
    //########################################

    public function getBackupTables()
    {
        return ['module_config'];
    }

    public function execute()
    {
        $select = $this->getConnection()->select()->from($this->getFullTableName('account'));
        $select->where('`component_mode` = ?', 'ebay');
        $select->where('`additional_data` like ?', '%"bopis":%');

        $pickupStoreAccounts = $this->getConnection()->fetchAll($select, [], \PDO::FETCH_ASSOC);

        $isPickupStoreEnabled = 0;
        foreach ($pickupStoreAccounts as $account) {
            $additionalData = json_decode($account['additional_data'], true);

            if (!$additionalData) {
                continue;
            }

            if (isset($additionalData['bopis']) && $additionalData['bopis']) {
                $isPickupStoreEnabled = 1;
                break;
            }
        }

        $this->getConfigModifier('module')->insert(
            '/ebay/in_store_pickup/', 'mode', $isPickupStoreEnabled, '0 - disable,\r\n1 - enable'
        );
    }

    //########################################
}
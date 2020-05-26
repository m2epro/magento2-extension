<?php

namespace Ess\M2ePro\Setup\Update\y20_m01;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;
use Magento\Framework\DB\Ddl\Table;

/**
 * Class \Ess\M2ePro\Setup\Update\y20_m01\EbayOrderUpdates
 */
class EbayOrderUpdates extends AbstractFeature
{
    //########################################

    public function execute()
    {
        $query = $this->installer->getConnection()
            ->select()
            ->from($this->getFullTableName('ebay_account'))
            ->query();

        while ($row = $query->fetch()) {
            $data = json_decode($row['magento_orders_settings'], true);

            /** Ess_M2ePro_Model_Ebay_Account::MAGENTO_ORDERS_CREATE_IMMEDIATELY = 1 */
            /** Ess_M2ePro_Model_Ebay_Account::MAGENTO_ORDERS_CREATE_PAID = 3 */
            /** Ess_M2ePro_Model_Ebay_Account::MAGENTO_ORDERS_CREATE_CHECKOUT_AND_PAID = 4 */

            if (isset($data['creation']['mode']) && $data['creation']['mode'] === 1) {
                $reservationDays = !empty($data['creation']['reservation_days'])
                    ? $data['creation']['reservation_days'] : 14;

                $data['creation']['mode'] = 4;
                $data['qty_reservation']['days'] = $reservationDays;
            }

            unset($data['creation']['reservation_days']);

            if (isset($data['creation']['mode']) && $data['creation']['mode'] === 3) {
                $data['creation']['mode'] = 4;
            }

            $this->installer->getConnection()->update(
                $this->getFullTableName('ebay_account'),
                ['magento_orders_settings' => json_encode($data)],
                ['account_id' => $row['account_id']]
            );
        }
    }

    //########################################
}

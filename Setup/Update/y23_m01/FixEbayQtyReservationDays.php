<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Update\y23_m01;

class FixEbayQtyReservationDays extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    /**
     * @return void
     * @throws \Zend_Db_Adapter_Exception
     * @throws \Zend_Db_Statement_Exception
     */
    public function execute()
    {
        $ebayAccountTableName = $this->getFullTableName('ebay_account');
        $query = $this
            ->getConnection()
            ->select()
            ->from($ebayAccountTableName,['magento_orders_settings', 'account_id'])
            ->query();

        while ($row = $query->fetch()) {
            $magentoOrderSettings = json_decode($row['magento_orders_settings'], true);

            if (!$this->isQtyReserveDaysZero($magentoOrderSettings)) {
                continue;
            }

            $magentoOrderSettings['qty_reservation']['days'] = 1;

            $this->getConnection()->update(
                $ebayAccountTableName,
                ['magento_orders_settings' => json_encode($magentoOrderSettings)],
                ['account_id = ?' => $row['account_id']]
            );
        }
    }

    /**
     * @param $magentoOrderSettings
     *
     * @return bool
     */
    private function isQtyReserveDaysZero($magentoOrderSettings): bool
    {
        if (!isset($magentoOrderSettings['qty_reservation']['days'])) {
            return false;
        }

        return (int)$magentoOrderSettings['qty_reservation']['days'] === 0;
    }
}

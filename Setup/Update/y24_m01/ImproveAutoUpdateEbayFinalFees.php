<?php

namespace Ess\M2ePro\Setup\Update\y24_m01;

class ImproveAutoUpdateEbayFinalFees extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    /**
     * @return void
     * @throws \Zend_Db_Adapter_Exception
     * @throws \Zend_Db_Statement_Exception
     */
    public function execute(): void
    {
        $tableName = $this->getFullTableName('ebay_account');
        $query = $this->getConnection()
                      ->select()
                      ->from($tableName)
                      ->query();

        while ($row = $query->fetch()) {
            $magentoOrdersSettings = json_decode($row['magento_orders_settings'], true);
            $magentoOrdersSettings['final_fee']['auto_retrieve_enabled'] = 0;

            $this->getConnection()->update(
                $tableName,
                ['magento_orders_settings' => json_encode($magentoOrdersSettings)],
                ['account_id = ?' => $row['account_id']]
            );
        }
    }
}

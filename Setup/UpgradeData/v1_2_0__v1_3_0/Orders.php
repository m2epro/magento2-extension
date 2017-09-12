<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2016 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\UpgradeData\v1_2_0__v1_3_0;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

class Orders extends AbstractFeature
{
    //########################################

    public function getBackupTables()
    {
        return ['synchronization_config', 'amazon_account', 'ebay_account'];
    }

    public function execute()
    {
        $amazonAccountTableName = $this->getFullTableName('amazon_account');

        if ($this->getTableModifier('amazon_account')->isColumnExists('orders_last_synchronization') &&
            $this->getTableModifier('amazon_account')->isColumnExists('merchant_id')) {

            $result = $this->getConnection()->query(<<<SQL
    SELECT aa.merchant_id,
           MIN(aa.orders_last_synchronization) as orders_last_synchronization
    FROM {$amazonAccountTableName} as aa
    WHERE aa.orders_last_synchronization IS NOT NULL
    GROUP BY aa.merchant_id
SQL
            )->fetchAll(\PDO::FETCH_ASSOC);

            foreach ($result as $item) {
                $this->getConfigModifier('synchronization')->insert(
                    "/amazon/orders/receive/{$item['merchant_id']}/",
                    "from_update_date",
                    $item['orders_last_synchronization']
                );
            }

            $this->getTableModifier('amazon_account')->dropColumn('orders_last_synchronization');
        }

        //----------------------------------------

        $this->getConfigModifier('synchronization')->insert(
            '/amazon/orders/update/', 'interval', '1800', 'in seconds'
        );

        //----------------------------------------

        $this->getTableModifier('ebay_account')->addColumn(
            'job_token', 'VARCHAR(255)', NULL, 'ebay_shipping_discount_profiles'
        );
    }

    //########################################
}
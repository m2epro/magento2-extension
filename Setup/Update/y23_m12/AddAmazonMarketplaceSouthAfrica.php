<?php

namespace Ess\M2ePro\Setup\Update\y23_m12;

class AddAmazonMarketplaceSouthAfrica extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    /**
     * @return void
     * @throws \Zend_Db_Statement_Exception
     */
    public function execute(): void
    {
        $marketplace = $this->installer->getConnection()->select()
                                       ->from($this->getFullTableName('marketplace'))
                                       ->where('id = ?', 49)
                                       ->query()
                                       ->fetch();

        if ($marketplace === false) {
            $this->installer->getConnection()->insert(
                $this->getFullTableName('marketplace'),
                [
                    'id'             => 49,
                    'native_id'      => 21,
                    'title'          => 'South Africa',
                    'code'           => 'ZA',
                    'url'            => 'amazon.co.za',
                    'status'         => 0,
                    'sorder'         => 23,
                    'group_title'    => 'Europe',
                    'component_mode' => 'amazon',
                    'update_date'    => '2023-12-14 00:00:00',
                    'create_date'    => '2023-12-14 00:00:00'
                ]
            );
        }

        $marketplace = $this->installer->getConnection()->select()
                                       ->from($this->getFullTableName('amazon_marketplace'))
                                       ->where('marketplace_id = ?', 49)
                                       ->query()
                                       ->fetch();

        if ($marketplace === false) {
            $this->installer->getConnection()->insert(
                $this->getFullTableName('amazon_marketplace'),
                [
                    'marketplace_id'                          => 49,
                    'default_currency'                        => 'ZAR',
                    'is_new_asin_available'                   => 1,
                    'is_merchant_fulfillment_available'       => 1,
                    'is_business_available'                   => 1,
                    'is_vat_calculation_service_available'    => 1,
                    'is_product_tax_code_policy_available'    => 1,
                ]
            );
        }
    }
}

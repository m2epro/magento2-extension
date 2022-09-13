<?php

namespace Ess\M2ePro\Setup\Update\y22_m09;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

class AddAmazonMarketplaceBelgium extends AbstractFeature
{
    /**
     * @return void
     * @throws \Zend_Db_Statement_Exception
     */
    public function execute(): void
    {
        $marketplace = $this->installer->getConnection()->select()
                                       ->from($this->getFullTableName('marketplace'))
                                       ->where('id = ?', 48)
                                       ->query()
                                       ->fetch();

        if ($marketplace === false) {
            $this->installer->getConnection()->insert(
                $this->getFullTableName('marketplace'),
                [
                    'id'             => 48,
                    'native_id'      => 20,
                    'title'          => 'Belgium',
                    'code'           => 'BE',
                    'url'            => 'amazon.com.be',
                    'status'         => 0,
                    'sorder'         => 22,
                    'group_title'    => 'Europe',
                    'component_mode' => 'amazon',
                    'update_date'    => '2022-09-01 00:00:00',
                    'create_date'    => '2022-09-01 00:00:00'
                ]
            );
        }

        $marketplace = $this->installer->getConnection()->select()
                                       ->from($this->getFullTableName('amazon_marketplace'))
                                       ->where('marketplace_id = ?', 48)
                                       ->query()
                                       ->fetch();

        if ($marketplace === false) {
            $this->installer->getConnection()->insert(
                $this->getFullTableName('amazon_marketplace'),
                [
                    'marketplace_id'                          => 48,
                    'developer_key'                           => '7078-7205-1944',
                    'default_currency'                        => 'EUR',
                    'is_new_asin_available'                   => 0,
                    'is_merchant_fulfillment_available'       => 1,
                    'is_business_available'                   => 1,
                    'is_vat_calculation_service_available'    => 0,
                    'is_product_tax_code_policy_available'    => 0,
                ]
            );
        }
    }
}

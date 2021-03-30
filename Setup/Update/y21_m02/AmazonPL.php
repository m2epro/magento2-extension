<?php

namespace Ess\M2ePro\Setup\Update\y21_m02;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

/**
 * Class \Ess\M2ePro\Setup\Update\y21_m02\AmazonPL
 */
class AmazonPL extends AbstractFeature
{
    //########################################

    public function execute()
    {
        $marketplace = $this->installer->getConnection()->select()
            ->from($this->getFullTableName('marketplace'))
            ->where('id = ?', 43)
            ->query()
            ->fetch();

        if ($marketplace === false) {
            $this->installer->getConnection()->insert(
                $this->getFullTableName('marketplace'),
                [
                    'id'             => 43,
                    'native_id'      => 15,
                    'title'          => 'Poland',
                    'code'           => 'PL',
                    'url'            => 'amazon.pl',
                    'status'         => 0,
                    'sorder'         => 17,
                    'group_title'    => 'Europe',
                    'component_mode' => 'amazon',
                    'update_date'    => '2021-02-01 00:00:00',
                    'create_date'    => '2021-02-01 00:00:00'
                ]
            );
        }

        $marketplace = $this->installer->getConnection()->select()
            ->from($this->getFullTableName('amazon_marketplace'))
            ->where('marketplace_id = ?', 43)
            ->query()
            ->fetch();

        if ($marketplace === false) {
            $this->installer->getConnection()->insert(
                $this->getFullTableName('amazon_marketplace'),
                [
                    'marketplace_id'                          => 43,
                    'developer_key'                           => '7078-7205-1944',
                    'default_currency'                        => 'PLN',
                    'is_new_asin_available'                   => 1,
                    'is_merchant_fulfillment_available'       => 1,
                    'is_business_available'                   => 0,
                    'is_vat_calculation_service_available'    => 0,
                    'is_product_tax_code_policy_available'    => 0,
                    'is_automatic_token_retrieving_available' => 1
                ]
            );
        }
    }

    //########################################
}

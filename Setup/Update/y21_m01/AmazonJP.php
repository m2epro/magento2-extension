<?php

namespace Ess\M2ePro\Setup\Update\y21_m01;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

/**
 * Class \Ess\M2ePro\Setup\Update\y21_m01\AmazonJP
 */
class AmazonJP extends AbstractFeature
{
    //########################################

    public function execute()
    {
        $marketplace = $this->installer->getConnection()->select()
            ->from($this->getFullTableName('marketplace'))
            ->where('id = ?', 42)
            ->query()
            ->fetch();

        if ($marketplace === false) {
            $this->installer->getConnection()->insert(
                $this->getFullTableName('marketplace'),
                [
                    'id'             => 42,
                    'native_id'      => 14,
                    'title'          => 'Japan',
                    'code'           => 'JP',
                    'url'            => 'amazon.co.jp',
                    'status'         => 0,
                    'sorder'         => 16,
                    'group_title'    => 'Asia / Pacific',
                    'component_mode' => 'amazon',
                    'update_date'    => '2021-01-11 00:00:00',
                    'create_date'    => '2021-01-11 00:00:00'
                ]
            );
        }

        $marketplace = $this->installer->getConnection()->select()
            ->from($this->getFullTableName('amazon_marketplace'))
            ->where('marketplace_id = ?', 42)
            ->query()
            ->fetch();

        if ($marketplace === false) {
            $this->installer->getConnection()->insert(
                $this->getFullTableName('amazon_marketplace'),
                [
                    'marketplace_id'                          => 42,
                    'developer_key'                           => '7078-7205-1944',
                    'default_currency'                        => 'JPY',
                    'is_new_asin_available'                   => 0,
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

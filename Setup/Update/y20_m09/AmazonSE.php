<?php

namespace Ess\M2ePro\Setup\Update\y20_m09;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

/**
 * Class \Ess\M2ePro\Setup\Update\y20_m09\AmazonTR
 */
class AmazonSE extends AbstractFeature
{
    //########################################

    public function execute()
    {
        $marketplace = $this->installer->getConnection()->select()
            ->from($this->getFullTableName('marketplace'))
            ->where('id = ?', 41)
            ->query()
            ->fetch();

        if ($marketplace === false) {
            $this->installer->getConnection()->insert(
                $this->getFullTableName('marketplace'),
                [
                    'id'             => 41,
                    'native_id'      => 13,
                    'title'          => 'Sweden',
                    'code'           => 'SE',
                    'url'            => 'amazon.se',
                    'status'         => 0,
                    'sorder'         => 15,
                    'group_title'    => 'Europe',
                    'component_mode' => 'amazon',
                    'update_date'    => '2020-09-03 00:00:00',
                    'create_date'    => '2020-09-03 00:00:00'
                ]
            );
        }

        $marketplace = $this->installer->getConnection()->select()
            ->from($this->getFullTableName('amazon_marketplace'))
            ->where('marketplace_id = ?', 41)
            ->query()
            ->fetch();

        if ($marketplace === false) {
            $this->installer->getConnection()->insert(
                $this->getFullTableName('amazon_marketplace'),
                [
                    'marketplace_id'                          => 41,
                    'developer_key'                           => '7078-7205-1944',
                    'default_currency'                        => 'SEK',
                    'is_new_asin_available'                   => 1,
                    'is_merchant_fulfillment_available'       => 1,
                    'is_business_available'                   => 0,
                    'is_vat_calculation_service_available'    => 0,
                    'is_product_tax_code_policy_available'    => 0,
                    'is_automatic_token_retrieving_available' => 1,
                    'is_upload_invoices_available'            => 0
                ]
            );
        }
    }

    //########################################
}

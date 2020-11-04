<?php

namespace Ess\M2ePro\Setup\Update\y20_m08;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

/**
 * Class \Ess\M2ePro\Setup\Update\y20_m08\AmazonTR
 */
class AmazonTR extends AbstractFeature
{
    //########################################

    public function execute()
    {
        $marketplace = $this->installer->getConnection()->select()
            ->from($this->getFullTableName('marketplace'))
            ->where('id = ?', 40)
            ->query()
            ->fetch();

        if ($marketplace === false) {
            $this->installer->getConnection()->insert(
                $this->getFullTableName('marketplace'),
                [
                    'id'             => 40,
                    'native_id'      => 12,
                    'title'          => 'Turkey',
                    'code'           => 'TR',
                    'url'            => 'amazon.com.tr',
                    'status'         => 0,
                    'sorder'         => 14,
                    'group_title'    => 'Europe',
                    'component_mode' => 'amazon',
                    'update_date'    => '2020-08-19 00:00:00',
                    'create_date'    => '2020-08-19 00:00:00'
                ]
            );
        }

        $marketplace = $this->installer->getConnection()->select()
            ->from($this->getFullTableName('amazon_marketplace'))
            ->where('marketplace_id = ?', 40)
            ->query()
            ->fetch();

        if ($marketplace === false) {
            $this->installer->getConnection()->insert(
                $this->getFullTableName('amazon_marketplace'),
                [
                    'marketplace_id'                          => 40,
                    'developer_key'                           => '7078-7205-1944',
                    'default_currency'                        => 'TRY',
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

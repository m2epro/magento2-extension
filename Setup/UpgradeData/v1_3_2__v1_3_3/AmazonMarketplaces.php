<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2016 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\UpgradeData\v1_3_2__v1_3_3;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

class AmazonMarketplaces extends AbstractFeature
{
    //########################################

    public function getBackupTables()
    {
        return ['amazon_marketplace'];
    }

    public function execute()
    {
        $this->getTableModifier('amazon_marketplace')
            ->addColumn('is_automatic_token_retrieving_available', 'SMALLINT(5) UNSIGNED NOT NULL', 0,
                'is_product_tax_code_policy_available', true);

        $this->getConnection()->update($this->getFullTableName('amazon_marketplace'),
            array('is_automatic_token_retrieving_available' => 1),
            array('marketplace_id IN (?)' => array(24, 25, 26, 28, 29, 30, 31))
        );

        $this->getConnection()->insertMultiple($this->getFullTableName('marketplace'), [
            [
                'id'             => 34,
                'native_id'      => 9,
                'title'          => 'Mexico',
                'code'           => 'MX',
                'url'            => 'amazon.com.mx',
                'status'         => 0,
                'sorder'         => 8,
                'group_title'    => 'America',
                'component_mode' => 'amazon',
                'update_date'    => '2017-10-17 00:00:00',
                'create_date'    => '2017-10-17 00:00:00'
            ],
            [
                'id'             => 35,
                'native_id'      => 10,
                'title'          => 'Australia',
                'code'           => 'AU',
                'url'            => 'amazon.com.au',
                'status'         => 0,
                'sorder'         => 1,
                'group_title'    => 'Asia / Pacific',
                'component_mode' => 'amazon',
                'update_date'    => '2017-10-17 00:00:00',
                'create_date'    => '2017-10-17 00:00:00'
            ]
        ]);

        $this->getConnection()->insertMultiple($this->getFullTableName('amazon_marketplace'), [
            [
                'marketplace_id'                          => 34,
                'developer_key'                           => '8636-1433-4377',
                'default_currency'                        => 'MXN',
                'is_new_asin_available'                   => 0,
                'is_merchant_fulfillment_available'       => 0,
                'is_business_available'                   => 0,
                'is_vat_calculation_service_available'    => 0,
                'is_product_tax_code_policy_available'    => 0,
                'is_automatic_token_retrieving_available' => 1,
            ],
            [
                'marketplace_id'                          => 35,
                'developer_key'                           => '2770-5005-3793',
                'default_currency'                        => 'AUD',
                'is_new_asin_available'                   => 1,
                'is_merchant_fulfillment_available'       => 0,
                'is_business_available'                   => 0,
                'is_vat_calculation_service_available'    => 0,
                'is_product_tax_code_policy_available'    => 0,
                'is_automatic_token_retrieving_available' => 0,
            ]
        ]);
    }

    //########################################
}
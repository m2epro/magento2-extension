<?php
// @codingStandardsIgnoreFile
/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup;

use Ess\M2ePro\Helper\Module;
use Ess\M2ePro\Model\Setup\Database\Modifier\Config;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;

/**
 * Class \Ess\M2ePro\Setup\InstallData
 */
class InstallData implements InstallDataInterface
{
    /** @var \Ess\M2ePro\Helper\Factory $helperFactory */
    private $helperFactory;

    /** @var \Ess\M2ePro\Model\Factory $modelFactory */
    private $modelFactory;

    /** @var \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory */
    private $activeRecordFactory;

    /** @var ModuleListInterface $moduleList */
    private $moduleList;

    /** @var \Magento\Framework\Module\ModuleResource $moduleResource */
    private $moduleResource;

    /** @var ModuleDataSetupInterface $installer */
    private $installer;

    /** @var \Psr\Log\LoggerInterface */
    private $logger;

    //########################################

    public function __construct(
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        ModuleListInterface $moduleList,
        \Ess\M2ePro\Setup\LoggerFactory $loggerFactory,
        \Magento\Framework\Model\ResourceModel\Db\Context $dbContext
    ) {
        $this->helperFactory = $helperFactory;
        $this->modelFactory = $modelFactory;
        $this->activeRecordFactory = $activeRecordFactory;
        $this->moduleList = $moduleList;
        $this->moduleResource = new \Magento\Framework\Module\ModuleResource($dbContext);

        $this->logger = $loggerFactory->create();
    }

    //########################################

    /**
     * Module versions from setup_module magento table uses only by magento for run install or upgrade files.
     * We do not use these versions in setup & upgrade logic (only set correct values to it, using m2epro_setup table).
     * So version, that presented in $context parameter, is not used.
     *
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface $context
     */
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $this->installer = $setup;

        if ($this->helperFactory->getObject('Data\GlobalData')->getValue('is_setup_failed')) {
            return;
        }

        if (!$this->helperFactory->getObject('Data\GlobalData')->getValue('is_install_process') ||
            !$this->helperFactory->getObject('Data\GlobalData')->getValue('is_install_schema_completed')) {
            return;
        }

        if ($this->isInstalled()) {
            return;
        }

        $this->installer->startSetup();

        try {

            $setupObject = $this->getCurrentSetupObject();

            $this->installGeneral();
            $this->installEbay();
            $this->installAmazon();
            $this->installWalmart();

        } catch (\Exception $exception) {
            $this->logger->error($exception, ['source' => 'InstallData']);
            $this->helperFactory->getObject('Data\GlobalData')->setValue('is_setup_failed', true);

            if (isset($setupObject)) {
                $setupObject->setData('profiler_data', $exception->__toString());
                $setupObject->save();
            }

            $this->installer->endSetup();

            return;
        }

        $setupObject->setData('is_completed', 1);
        $setupObject->save();

        $this->moduleResource->setDbVersion(\Ess\M2ePro\Helper\Module::IDENTIFIER, $this->getConfigVersion());
        $this->moduleResource->setDataVersion(\Ess\M2ePro\Helper\Module::IDENTIFIER, $this->getConfigVersion());

        $this->helperFactory->getObject('Module\Maintenance')->disable();
        $this->installer->endSetup();
    }

    //########################################

    private function installGeneral()
    {
        $magentoMarketplaceUrl = 'https://marketplace.magento.com/m2e-ebay-amazon-magento2.html';
        $servicingInterval = rand(43200, 86400);

        $moduleConfig = $this->getConfigModifier();

        $moduleConfig->insert('/', 'is_disabled', '0');
        $moduleConfig->insert('/', 'environment', 'production');
        $moduleConfig->insert('/', 'installation_key', sha1(microtime(1)));
        $moduleConfig->insert('/license/', 'key', null);
        $moduleConfig->insert('/license/', 'status', 1);
        $moduleConfig->insert('/license/domain/', 'real', null);
        $moduleConfig->insert('/license/domain/', 'valid', null);
        $moduleConfig->insert('/license/domain/', 'is_valid', null);
        $moduleConfig->insert('/license/ip/', 'real', null);
        $moduleConfig->insert('/license/ip/', 'valid', null);
        $moduleConfig->insert('/license/ip/', 'is_valid', null);
        $moduleConfig->insert('/license/info/', 'email', null);
        $moduleConfig->insert('/server/', 'application_key', '02edcc129b6128f5fa52d4ad1202b427996122b6');
        $moduleConfig->insert('/server/location/1/', 'baseurl', 'https://s1.m2epro.com/');
        $moduleConfig->insert('/server/location/', 'default_index', 1);
        $moduleConfig->insert('/server/location/', 'current_index', 1);
        $moduleConfig->insert('/server/exceptions/', 'send', '1');
        $moduleConfig->insert('/server/exceptions/', 'filters', '0');
        $moduleConfig->insert('/server/fatal_error/', 'send', '1');
        $moduleConfig->insert('/server/logging/', 'send', 1);
        $moduleConfig->insert('/cron/', 'mode', '1');
        $moduleConfig->insert('/cron/', 'runner', 'magento');
        $moduleConfig->insert('/cron/', 'last_access', null);
        $moduleConfig->insert('/cron/', 'last_runner_change', null);
        $moduleConfig->insert('/cron/', 'last_executed_slow_task', null);
        $moduleConfig->insert('/cron/', 'last_executed_task_group', null);
        $moduleConfig->insert('/cron/service/', 'auth_key', null);
        $moduleConfig->insert('/cron/service_controller/', 'disabled', '0');
        $moduleConfig->insert('/cron/service_pub/', 'disabled', '0');
        $moduleConfig->insert('/cron/magento/', 'disabled', '0');
        $moduleConfig->insert('/cron/task/system/servicing/synchronize/', 'interval', $servicingInterval);
        $moduleConfig->insert('/logs/clearing/listings/', 'mode', '1');
        $moduleConfig->insert('/logs/clearing/listings/', 'days', '30');
        $moduleConfig->insert('/logs/clearing/synchronizations/', 'mode', '1');
        $moduleConfig->insert('/logs/clearing/synchronizations/', 'days', '30');
        $moduleConfig->insert('/logs/clearing/orders/', 'mode', '1');
        $moduleConfig->insert('/logs/clearing/orders/', 'days', '90');
        $moduleConfig->insert('/logs/clearing/ebay_pickup_store/', 'mode', '1');
        $moduleConfig->insert('/logs/clearing/ebay_pickup_store/', 'days', '30');
        $moduleConfig->insert('/logs/listings/', 'last_action_id', '0');
        $moduleConfig->insert('/logs/ebay_pickup_store/', 'last_action_id', '0');
        $moduleConfig->insert('/logs/grouped/', 'max_records_count', '100000');
        $moduleConfig->insert('/support/', 'documentation_url', 'https://docs.m2epro.com/');
        $moduleConfig->insert('/support/', 'clients_portal_url', 'https://clients.m2epro.com/');
        $moduleConfig->insert('/support/', 'website_url', 'https://m2epro.com/');
        $moduleConfig->insert('/support/', 'support_url', 'https://support.m2epro.com/');
        $moduleConfig->insert('/support/', 'forum_url', 'https://community.m2epro.com/');
        $moduleConfig->insert('/support/', 'magento_marketplace_url', $magentoMarketplaceUrl);
        $moduleConfig->insert('/support/', 'contact_email', 'support@m2epro.com');
        $moduleConfig->insert('/general/configuration/', 'listing_product_inspector_mode', '0');
        $moduleConfig->insert('/general/configuration/', 'view_show_block_notices_mode', '1');
        $moduleConfig->insert('/general/configuration/', 'view_show_products_thumbnails_mode', '1');
        $moduleConfig->insert('/general/configuration/', 'view_products_grid_use_alternative_mysql_select_mode', '0');
        $moduleConfig->insert('/general/configuration/', 'renderer_description_convert_linebreaks_mode', '1');
        $moduleConfig->insert('/general/configuration/', 'other_pay_pal_url', 'paypal.com/cgi-bin/webscr/');
        $moduleConfig->insert('/general/configuration/', 'product_index_mode', '1');
        $moduleConfig->insert('/general/configuration/', 'product_force_qty_mode', '0');
        $moduleConfig->insert('/general/configuration/', 'product_force_qty_value', '10');
        $moduleConfig->insert('/general/configuration/', 'qty_percentage_rounding_greater', '0');
        $moduleConfig->insert('/general/configuration/', 'magento_attribute_price_type_converting_mode', '0');
        $moduleConfig->insert(
            '/general/configuration/',
            'create_with_first_product_options_when_variation_unavailable',
            '1'
        );
        $moduleConfig->insert('/general/configuration/', 'secure_image_url_in_item_description_mode', '0');
        $moduleConfig->insert('/general/configuration/', 'grouped_product_mode', '0');
        $moduleConfig->insert('/magento/product/simple_type/', 'custom_types', '');
        $moduleConfig->insert('/magento/product/downloadable_type/', 'custom_types', '');
        $moduleConfig->insert('/magento/product/configurable_type/', 'custom_types', '');
        $moduleConfig->insert('/magento/product/bundle_type/', 'custom_types', '');
        $moduleConfig->insert('/magento/product/grouped_type/', 'custom_types', '');
        $moduleConfig->insert('/health_status/notification/', 'mode', 1);
        $moduleConfig->insert('/health_status/notification/', 'email', '');
        $moduleConfig->insert('/health_status/notification/', 'level', 40);

        $this->getConnection()->insertMultiple(
            $this->getFullTableName('wizard'),
            [
                [
                    'nick'     => 'installationEbay',
                    'view'     => 'ebay',
                    'status'   => 0,
                    'step'     => null,
                    'type'     => 1,
                    'priority' => 2,
                ],
                [
                    'nick'     => 'installationAmazon',
                    'view'     => 'amazon',
                    'status'   => 0,
                    'step'     => null,
                    'type'     => 1,
                    'priority' => 3,
                ],
                [
                    'nick'     => 'installationWalmart',
                    'view'     => 'walmart',
                    'status'   => 0,
                    'step'     => null,
                    'type'     => 1,
                    'priority' => 4,
                ],
                [
                    'nick'     => 'migrationFromMagento1',
                    'view'     => '*',
                    'status'   => 2,
                    'step'     => null,
                    'type'     => 1,
                    'priority' => 1,
                ],
                [
                    'nick'     => 'migrationToInnodb',
                    'view'     => '*',
                    'status'   => 3,
                    'step'     => null,
                    'type'     => 1,
                    'priority' => 5,
                ]
            ]
        );
    }

    private function installEbay()
    {
        $moduleConfig = $this->getConfigModifier();

        $moduleConfig->insert('/component/ebay/', 'mode', '1');
        $moduleConfig->insert('/cron/task/ebay/listing/product/process_instructions/', 'mode', '1');
        $moduleConfig->insert('/listing/product/inspector/ebay/', 'max_allowed_instructions_count', '2000');
        $moduleConfig->insert('/ebay/listing/product/instructions/cron/', 'listings_products_per_one_time', '1000');
        $moduleConfig->insert('/ebay/listing/product/scheduled_actions/', 'max_prepared_actions_count', '3000');
        $moduleConfig->insert('/ebay/order/settings/marketplace_8/', 'use_first_street_line_as_company', '1');
        $moduleConfig->insert('/ebay/configuration/', 'prevent_item_duplicates_mode', '1');
        $moduleConfig->insert('/ebay/configuration/', 'variation_mpn_can_be_changed', '0');
        $moduleConfig->insert('/ebay/configuration/', 'motors_epids_attribute', null);
        $moduleConfig->insert('/ebay/configuration/', 'uk_epids_attribute', null);
        $moduleConfig->insert('/ebay/configuration/', 'de_epids_attribute', null);
        $moduleConfig->insert('/ebay/configuration/', 'au_epids_attribute', null);
        $moduleConfig->insert('/ebay/configuration/', 'ktypes_attribute', null);
        $moduleConfig->insert('/ebay/configuration/', 'upload_images_mode', 2);
        $moduleConfig->insert('/ebay/configuration/', 'view_template_selling_format_show_tax_category', '0');
        $moduleConfig->insert('/ebay/configuration/', 'feedback_notification_mode', '0');
        $moduleConfig->insert('/ebay/configuration/', 'feedback_notification_last_check', null);

        $this->getConnection()->insertMultiple(
            $this->getFullTableName('marketplace'),
            [
                [
                    'id'             => 1,
                    'native_id'      => 0,
                    'title'          => 'United States',
                    'code'           => 'US',
                    'url'            => 'ebay.com',
                    'status'         => 0,
                    'sorder'         => 1,
                    'group_title'    => 'America',
                    'component_mode' => 'ebay',
                    'update_date'    => '2013-05-08 00:00:00',
                    'create_date'    => '2013-05-08 00:00:00'
                ],
                [
                    'id'             => 2,
                    'native_id'      => 2,
                    'title'          => 'Canada',
                    'code'           => 'Canada',
                    'url'            => 'ebay.ca',
                    'status'         => 0,
                    'sorder'         => 8,
                    'group_title'    => 'America',
                    'component_mode' => 'ebay',
                    'update_date'    => '2013-05-08 00:00:00',
                    'create_date'    => '2013-05-08 00:00:00'
                ],
                [
                    'id'             => 3,
                    'native_id'      => 3,
                    'title'          => 'United Kingdom',
                    'code'           => 'UK',
                    'url'            => 'ebay.co.uk',
                    'status'         => 0,
                    'sorder'         => 2,
                    'group_title'    => 'Europe',
                    'component_mode' => 'ebay',
                    'update_date'    => '2013-05-08 00:00:00',
                    'create_date'    => '2013-05-08 00:00:00'
                ],
                [
                    'id'             => 4,
                    'native_id'      => 15,
                    'title'          => 'Australia',
                    'code'           => 'Australia',
                    'url'            => 'ebay.com.au',
                    'status'         => 0,
                    'sorder'         => 4,
                    'group_title'    => 'Asia / Pacific',
                    'component_mode' => 'ebay',
                    'update_date'    => '2013-05-08 00:00:00',
                    'create_date'    => '2013-05-08 00:00:00'
                ],
                [
                    'id'             => 5,
                    'native_id'      => 16,
                    'title'          => 'Austria',
                    'code'           => 'Austria',
                    'url'            => 'ebay.at',
                    'status'         => 0,
                    'sorder'         => 5,
                    'group_title'    => 'Europe',
                    'component_mode' => 'ebay',
                    'update_date'    => '2013-05-08 00:00:00',
                    'create_date'    => '2013-05-08 00:00:00'
                ],
                [
                    'id'             => 6,
                    'native_id'      => 23,
                    'title'          => 'Belgium (French)',
                    'code'           => 'Belgium_French',
                    'url'            => 'befr.ebay.be',
                    'status'         => 0,
                    'sorder'         => 7,
                    'group_title'    => 'Europe',
                    'component_mode' => 'ebay',
                    'update_date'    => '2013-05-08 00:00:00',
                    'create_date'    => '2013-05-08 00:00:00'
                ],
                [
                    'id'             => 7,
                    'native_id'      => 71,
                    'title'          => 'France',
                    'code'           => 'France',
                    'url'            => 'ebay.fr',
                    'status'         => 0,
                    'sorder'         => 10,
                    'group_title'    => 'Europe',
                    'component_mode' => 'ebay',
                    'update_date'    => '2013-05-08 00:00:00',
                    'create_date'    => '2013-05-08 00:00:00'
                ],
                [
                    'id'             => 8,
                    'native_id'      => 77,
                    'title'          => 'Germany',
                    'code'           => 'Germany',
                    'url'            => 'ebay.de',
                    'status'         => 0,
                    'sorder'         => 3,
                    'group_title'    => 'Europe',
                    'component_mode' => 'ebay',
                    'update_date'    => '2013-05-08 00:00:00',
                    'create_date'    => '2013-05-08 00:00:00'
                ],
                [
                    'id'             => 9,
                    'native_id'      => 100,
                    'title'          => 'eBay Motors',
                    'code'           => 'eBayMotors',
                    'url'            => 'ebay.com/motors',
                    'status'         => 0,
                    'sorder'         => 23,
                    'group_title'    => 'Other',
                    'component_mode' => 'ebay',
                    'update_date'    => '2013-05-08 00:00:00',
                    'create_date'    => '2013-05-08 00:00:00'
                ],
                [
                    'id'             => 10,
                    'native_id'      => 101,
                    'title'          => 'Italy',
                    'code'           => 'Italy',
                    'url'            => 'ebay.it',
                    'status'         => 0,
                    'sorder'         => 14,
                    'group_title'    => 'Europe',
                    'component_mode' => 'ebay',
                    'update_date'    => '2013-05-08 00:00:00',
                    'create_date'    => '2013-05-08 00:00:00'
                ],
                [
                    'id'             => 11,
                    'native_id'      => 123,
                    'title'          => 'Belgium (Dutch)',
                    'code'           => 'Belgium_Dutch',
                    'url'            => 'benl.ebay.be',
                    'status'         => 0,
                    'sorder'         => 6,
                    'group_title'    => 'Europe',
                    'component_mode' => 'ebay',
                    'update_date'    => '2013-05-08 00:00:00',
                    'create_date'    => '2013-05-08 00:00:00'
                ],
                [
                    'id'             => 12,
                    'native_id'      => 146,
                    'title'          => 'Netherlands',
                    'code'           => 'Netherlands',
                    'url'            => 'ebay.nl',
                    'status'         => 0,
                    'sorder'         => 16,
                    'group_title'    => 'Europe',
                    'component_mode' => 'ebay',
                    'update_date'    => '2013-05-08 00:00:00',
                    'create_date'    => '2013-05-08 00:00:00'
                ],
                [
                    'id'             => 13,
                    'native_id'      => 186,
                    'title'          => 'Spain',
                    'code'           => 'Spain',
                    'url'            => 'ebay.es',
                    'status'         => 0,
                    'sorder'         => 19,
                    'group_title'    => 'Europe',
                    'component_mode' => 'ebay',
                    'update_date'    => '2013-05-08 00:00:00',
                    'create_date'    => '2013-05-08 00:00:00'
                ],
                [
                    'id'             => 14,
                    'native_id'      => 193,
                    'title'          => 'Switzerland',
                    'code'           => 'Switzerland',
                    'url'            => 'ebay.ch',
                    'status'         => 0,
                    'sorder'         => 22,
                    'group_title'    => 'Europe',
                    'component_mode' => 'ebay',
                    'update_date'    => '2013-05-08 00:00:00',
                    'create_date'    => '2013-05-08 00:00:00'
                ],
                [
                    'id'             => 15,
                    'native_id'      => 201,
                    'title'          => 'Hong Kong',
                    'code'           => 'HongKong',
                    'url'            => 'ebay.com.hk',
                    'status'         => 0,
                    'sorder'         => 11,
                    'group_title'    => 'Asia / Pacific',
                    'component_mode' => 'ebay',
                    'update_date'    => '2013-05-08 00:00:00',
                    'create_date'    => '2013-05-08 00:00:00'
                ],
                [
                    'id'             => 16,
                    'native_id'      => 203,
                    'title'          => 'India',
                    'code'           => 'India',
                    'url'            => 'ebay.in',
                    'status'         => 0,
                    'sorder'         => 12,
                    'group_title'    => 'Asia / Pacific',
                    'component_mode' => 'ebay',
                    'update_date'    => '2013-05-08 00:00:00',
                    'create_date'    => '2013-05-08 00:00:00'
                ],
                [
                    'id'             => 17,
                    'native_id'      => 205,
                    'title'          => 'Ireland',
                    'code'           => 'Ireland',
                    'url'            => 'ebay.ie',
                    'status'         => 0,
                    'sorder'         => 13,
                    'group_title'    => 'Europe',
                    'component_mode' => 'ebay',
                    'update_date'    => '2013-05-08 00:00:00',
                    'create_date'    => '2013-05-08 00:00:00'
                ],
                [
                    'id'             => 18,
                    'native_id'      => 207,
                    'title'          => 'Malaysia',
                    'code'           => 'Malaysia',
                    'url'            => 'ebay.com.my',
                    'status'         => 0,
                    'sorder'         => 15,
                    'group_title'    => 'Asia / Pacific',
                    'component_mode' => 'ebay',
                    'update_date'    => '2013-05-08 00:00:00',
                    'create_date'    => '2013-05-08 00:00:00'
                ],
                [
                    'id'             => 19,
                    'native_id'      => 210,
                    'title'          => 'Canada (French)',
                    'code'           => 'CanadaFrench',
                    'url'            => 'cafr.ebay.ca',
                    'status'         => 0,
                    'sorder'         => 9,
                    'group_title'    => 'America',
                    'component_mode' => 'ebay',
                    'update_date'    => '2013-05-08 00:00:00',
                    'create_date'    => '2013-05-08 00:00:00'
                ],
                [
                    'id'             => 20,
                    'native_id'      => 211,
                    'title'          => 'Philippines',
                    'code'           => 'Philippines',
                    'url'            => 'ebay.ph',
                    'status'         => 0,
                    'sorder'         => 17,
                    'group_title'    => 'Asia / Pacific',
                    'component_mode' => 'ebay',
                    'update_date'    => '2013-05-08 00:00:00',
                    'create_date'    => '2013-05-08 00:00:00'
                ],
                [
                    'id'             => 21,
                    'native_id'      => 212,
                    'title'          => 'Poland',
                    'code'           => 'Poland',
                    'url'            => 'ebay.pl',
                    'status'         => 0,
                    'sorder'         => 18,
                    'group_title'    => 'Europe',
                    'component_mode' => 'ebay',
                    'update_date'    => '2013-05-08 00:00:00',
                    'create_date'    => '2013-05-08 00:00:00'
                ],
                [
                    'id'             => 22,
                    'native_id'      => 216,
                    'title'          => 'Singapore',
                    'code'           => 'Singapore',
                    'url'            => 'ebay.com.sg',
                    'status'         => 0,
                    'sorder'         => 20,
                    'group_title'    => 'Asia / Pacific',
                    'component_mode' => 'ebay',
                    'update_date'    => '2013-05-08 00:00:00',
                    'create_date'    => '2013-05-08 00:00:00'
                ]
            ]
        );

        $this->getConnection()->insertMultiple(
            $this->getFullTableName('ebay_marketplace'),
            [
                [
                    'marketplace_id'                       => 1,
                    'currency'                             => 'USD',
                    'origin_country'                       => 'us',
                    'language_code'                        => 'en_US',
                    'is_multivariation'                    => 1,
                    'is_freight_shipping'                  => 1,
                    'is_calculated_shipping'               => 1,
                    'is_tax_table'                         => 1,
                    'is_vat'                               => 0,
                    'is_stp'                               => 1,
                    'is_stp_advanced'                      => 0,
                    'is_map'                               => 1,
                    'is_local_shipping_rate_table'         => 1,
                    'is_international_shipping_rate_table' => 1,
                    'is_english_measurement_system'        => 1,
                    'is_metric_measurement_system'         => 0,
                    'is_managed_payments'                  => 1,
                    'is_cash_on_delivery'                  => 0,
                    'is_global_shipping_program'           => 1,
                    'is_charity'                           => 1,
                    'is_in_store_pickup'                   => 1,
                    'is_return_description'                => 0,
                    'is_epid'                              => 0,
                    'is_ktype'                             => 0
                ],
                [
                    'marketplace_id'                       => 2,
                    'currency'                             => 'CAD',
                    'origin_country'                       => 'ca',
                    'language_code'                        => 'en_CA',
                    'is_multivariation'                    => 1,
                    'is_freight_shipping'                  => 1,
                    'is_calculated_shipping'               => 1,
                    'is_tax_table'                         => 1,
                    'is_vat'                               => 0,
                    'is_stp'                               => 1,
                    'is_stp_advanced'                      => 0,
                    'is_map'                               => 0,
                    'is_local_shipping_rate_table'         => 1,
                    'is_international_shipping_rate_table' => 1,
                    'is_english_measurement_system'        => 1,
                    'is_metric_measurement_system'         => 1,
                    'is_managed_payments'                  => 1,
                    'is_cash_on_delivery'                  => 0,
                    'is_global_shipping_program'           => 0,
                    'is_charity'                           => 1,
                    'is_in_store_pickup'                   => 1,
                    'is_return_description'                => 0,
                    'is_epid'                              => 0,
                    'is_ktype'                             => 0
                ],
                [
                    'marketplace_id'                       => 3,
                    'currency'                             => 'GBP',
                    'origin_country'                       => 'gb',
                    'language_code'                        => 'en_GB',
                    'is_multivariation'                    => 1,
                    'is_freight_shipping'                  => 1,
                    'is_calculated_shipping'               => 0,
                    'is_tax_table'                         => 0,
                    'is_vat'                               => 1,
                    'is_stp'                               => 1,
                    'is_stp_advanced'                      => 1,
                    'is_map'                               => 0,
                    'is_local_shipping_rate_table'         => 1,
                    'is_international_shipping_rate_table' => 1,
                    'is_english_measurement_system'        => 0,
                    'is_metric_measurement_system'         => 1,
                    'is_managed_payments'                  => 1,
                    'is_cash_on_delivery'                  => 0,
                    'is_global_shipping_program'           => 1,
                    'is_charity'                           => 1,
                    'is_in_store_pickup'                   => 1,
                    'is_return_description'                => 0,
                    'is_epid'                              => 1,
                    'is_ktype'                             => 1
                ],
                [
                    'marketplace_id'                       => 4,
                    'currency'                             => 'AUD',
                    'origin_country'                       => 'au',
                    'language_code'                        => 'en_AU',
                    'is_multivariation'                    => 1,
                    'is_freight_shipping'                  => 1,
                    'is_calculated_shipping'               => 1,
                    'is_tax_table'                         => 0,
                    'is_vat'                               => 0,
                    'is_stp'                               => 1,
                    'is_stp_advanced'                      => 0,
                    'is_map'                               => 0,
                    'is_local_shipping_rate_table'         => 1,
                    'is_international_shipping_rate_table' => 0,
                    'is_english_measurement_system'        => 0,
                    'is_metric_measurement_system'         => 1,
                    'is_managed_payments'                  => 1,
                    'is_cash_on_delivery'                  => 0,
                    'is_global_shipping_program'           => 0,
                    'is_charity'                           => 1,
                    'is_in_store_pickup'                   => 1,
                    'is_return_description'                => 0,
                    'is_epid'                              => 1,
                    'is_ktype'                             => 1
                ],
                [
                    'marketplace_id'                       => 5,
                    'currency'                             => 'EUR',
                    'origin_country'                       => 'at',
                    'language_code'                        => 'de_AT',
                    'is_multivariation'                    => 1,
                    'is_freight_shipping'                  => 0,
                    'is_calculated_shipping'               => 0,
                    'is_tax_table'                         => 0,
                    'is_vat'                               => 1,
                    'is_stp'                               => 0,
                    'is_stp_advanced'                      => 0,
                    'is_map'                               => 0,
                    'is_local_shipping_rate_table'         => 0,
                    'is_international_shipping_rate_table' => 0,
                    'is_english_measurement_system'        => 0,
                    'is_metric_measurement_system'         => 1,
                    'is_managed_payments'                  => 0,
                    'is_cash_on_delivery'                  => 0,
                    'is_global_shipping_program'           => 0,
                    'is_charity'                           => 1,
                    'is_in_store_pickup'                   => 0,
                    'is_return_description'                => 1,
                    'is_epid'                              => 0,
                    'is_ktype'                             => 0
                ],
                [
                    'marketplace_id'                       => 6,
                    'currency'                             => 'EUR',
                    'origin_country'                       => 'be',
                    'language_code'                        => 'nl_BE',
                    'is_multivariation'                    => 0,
                    'is_freight_shipping'                  => 0,
                    'is_calculated_shipping'               => 0,
                    'is_tax_table'                         => 0,
                    'is_vat'                               => 1,
                    'is_stp'                               => 0,
                    'is_stp_advanced'                      => 0,
                    'is_map'                               => 0,
                    'is_local_shipping_rate_table'         => 0,
                    'is_international_shipping_rate_table' => 0,
                    'is_english_measurement_system'        => 0,
                    'is_metric_measurement_system'         => 1,
                    'is_managed_payments'                  => 0,
                    'is_cash_on_delivery'                  => 0,
                    'is_global_shipping_program'           => 0,
                    'is_charity'                           => 1,
                    'is_in_store_pickup'                   => 0,
                    'is_return_description'                => 0,
                    'is_epid'                              => 0,
                    'is_ktype'                             => 0
                ],
                [
                    'marketplace_id'                       => 7,
                    'currency'                             => 'EUR',
                    'origin_country'                       => 'fr',
                    'language_code'                        => 'fr_FR',
                    'is_multivariation'                    => 1,
                    'is_freight_shipping'                  => 0,
                    'is_calculated_shipping'               => 0,
                    'is_tax_table'                         => 0,
                    'is_vat'                               => 1,
                    'is_stp'                               => 1,
                    'is_stp_advanced'                      => 0,
                    'is_map'                               => 0,
                    'is_local_shipping_rate_table'         => 0,
                    'is_international_shipping_rate_table' => 0,
                    'is_english_measurement_system'        => 0,
                    'is_metric_measurement_system'         => 1,
                    'is_managed_payments'                  => 1,
                    'is_cash_on_delivery'                  => 0,
                    'is_global_shipping_program'           => 0,
                    'is_charity'                           => 1,
                    'is_in_store_pickup'                   => 0,
                    'is_return_description'                => 1,
                    'is_epid'                              => 0,
                    'is_ktype'                             => 1
                ],
                [
                    'marketplace_id'                       => 8,
                    'currency'                             => 'EUR',
                    'origin_country'                       => 'de',
                    'language_code'                        => 'de_DE',
                    'is_multivariation'                    => 1,
                    'is_freight_shipping'                  => 0,
                    'is_calculated_shipping'               => 0,
                    'is_tax_table'                         => 0,
                    'is_vat'                               => 1,
                    'is_stp'                               => 1,
                    'is_stp_advanced'                      => 1,
                    'is_map'                               => 0,
                    'is_local_shipping_rate_table'         => 1,
                    'is_international_shipping_rate_table' => 1,
                    'is_english_measurement_system'        => 0,
                    'is_metric_measurement_system'         => 1,
                    'is_managed_payments'                  => 1,
                    'is_cash_on_delivery'                  => 0,
                    'is_global_shipping_program'           => 0,
                    'is_charity'                           => 1,
                    'is_in_store_pickup'                   => 1,
                    'is_return_description'                => 1,
                    'is_epid'                              => 1,
                    'is_ktype'                             => 1
                ],
                [
                    'marketplace_id'                       => 9,
                    'currency'                             => 'USD',
                    'origin_country'                       => 'us',
                    'language_code'                        => 'en_US',
                    'is_multivariation'                    => 1,
                    'is_freight_shipping'                  => 0,
                    'is_calculated_shipping'               => 1,
                    'is_tax_table'                         => 1,
                    'is_vat'                               => 0,
                    'is_stp'                               => 1,
                    'is_stp_advanced'                      => 0,
                    'is_map'                               => 0,
                    'is_local_shipping_rate_table'         => 1,
                    'is_international_shipping_rate_table' => 0,
                    'is_english_measurement_system'        => 1,
                    'is_metric_measurement_system'         => 0,
                    'is_managed_payments'                  => 1,
                    'is_cash_on_delivery'                  => 0,
                    'is_global_shipping_program'           => 1,
                    'is_charity'                           => 1,
                    'is_in_store_pickup'                   => 0,
                    'is_return_description'                => 0,
                    'is_epid'                              => 1,
                    'is_ktype'                             => 0
                ],
                [
                    'marketplace_id'                       => 10,
                    'currency'                             => 'EUR',
                    'origin_country'                       => 'it',
                    'language_code'                        => 'it_IT',
                    'is_multivariation'                    => 1,
                    'is_freight_shipping'                  => 0,
                    'is_calculated_shipping'               => 0,
                    'is_tax_table'                         => 0,
                    'is_vat'                               => 1,
                    'is_stp'                               => 1,
                    'is_stp_advanced'                      => 0,
                    'is_map'                               => 0,
                    'is_local_shipping_rate_table'         => 1,
                    'is_international_shipping_rate_table' => 1,
                    'is_english_measurement_system'        => 0,
                    'is_metric_measurement_system'         => 1,
                    'is_managed_payments'                  => 1,
                    'is_cash_on_delivery'                  => 1,
                    'is_global_shipping_program'           => 0,
                    'is_charity'                           => 1,
                    'is_in_store_pickup'                   => 0,
                    'is_return_description'                => 1,
                    'is_epid'                              => 0,
                    'is_ktype'                             => 1
                ],
                [
                    'marketplace_id'                       => 11,
                    'currency'                             => 'EUR',
                    'origin_country'                       => 'be',
                    'language_code'                        => 'fr_BE',
                    'is_multivariation'                    => 0,
                    'is_freight_shipping'                  => 0,
                    'is_calculated_shipping'               => 0,
                    'is_tax_table'                         => 0,
                    'is_vat'                               => 1,
                    'is_stp'                               => 0,
                    'is_stp_advanced'                      => 0,
                    'is_map'                               => 0,
                    'is_local_shipping_rate_table'         => 0,
                    'is_international_shipping_rate_table' => 0,
                    'is_english_measurement_system'        => 0,
                    'is_metric_measurement_system'         => 1,
                    'is_managed_payments'                  => 0,
                    'is_cash_on_delivery'                  => 0,
                    'is_global_shipping_program'           => 0,
                    'is_charity'                           => 1,
                    'is_in_store_pickup'                   => 0,
                    'is_return_description'                => 0,
                    'is_epid'                              => 0,
                    'is_ktype'                             => 0
                ],
                [
                    'marketplace_id'                       => 12,
                    'currency'                             => 'EUR',
                    'origin_country'                       => 'nl',
                    'language_code'                        => 'nl_NL',
                    'is_multivariation'                    => 1,
                    'is_freight_shipping'                  => 0,
                    'is_calculated_shipping'               => 0,
                    'is_tax_table'                         => 0,
                    'is_vat'                               => 1,
                    'is_stp'                               => 0,
                    'is_stp_advanced'                      => 0,
                    'is_map'                               => 0,
                    'is_local_shipping_rate_table'         => 0,
                    'is_international_shipping_rate_table' => 0,
                    'is_english_measurement_system'        => 0,
                    'is_metric_measurement_system'         => 1,
                    'is_managed_payments'                  => 0,
                    'is_cash_on_delivery'                  => 0,
                    'is_global_shipping_program'           => 0,
                    'is_charity'                           => 1,
                    'is_in_store_pickup'                   => 0,
                    'is_return_description'                => 0,
                    'is_epid'                              => 0,
                    'is_ktype'                             => 0
                ],
                [
                    'marketplace_id'                       => 13,
                    'currency'                             => 'EUR',
                    'origin_country'                       => 'es',
                    'language_code'                        => 'es_ES',
                    'is_multivariation'                    => 1,
                    'is_freight_shipping'                  => 0,
                    'is_calculated_shipping'               => 0,
                    'is_tax_table'                         => 0,
                    'is_vat'                               => 1,
                    'is_stp'                               => 1,
                    'is_stp_advanced'                      => 0,
                    'is_map'                               => 0,
                    'is_local_shipping_rate_table'         => 0,
                    'is_international_shipping_rate_table' => 0,
                    'is_english_measurement_system'        => 0,
                    'is_metric_measurement_system'         => 1,
                    'is_managed_payments'                  => 1,
                    'is_cash_on_delivery'                  => 0,
                    'is_global_shipping_program'           => 0,
                    'is_charity'                           => 1,
                    'is_in_store_pickup'                   => 0,
                    'is_return_description'                => 1,
                    'is_epid'                              => 0,
                    'is_ktype'                             => 1
                ],
                [
                    'marketplace_id'                       => 14,
                    'currency'                             => 'CHF',
                    'origin_country'                       => 'ch',
                    'language_code'                        => 'fr_CH',
                    'is_multivariation'                    => 1,
                    'is_freight_shipping'                  => 0,
                    'is_calculated_shipping'               => 0,
                    'is_tax_table'                         => 0,
                    'is_vat'                               => 1,
                    'is_stp'                               => 0,
                    'is_stp_advanced'                      => 0,
                    'is_map'                               => 0,
                    'is_local_shipping_rate_table'         => 0,
                    'is_international_shipping_rate_table' => 0,
                    'is_english_measurement_system'        => 0,
                    'is_metric_measurement_system'         => 1,
                    'is_managed_payments'                  => 0,
                    'is_cash_on_delivery'                  => 0,
                    'is_global_shipping_program'           => 0,
                    'is_charity'                           => 1,
                    'is_in_store_pickup'                   => 0,
                    'is_return_description'                => 0,
                    'is_epid'                              => 0,
                    'is_ktype'                             => 0
                ],
                [
                    'marketplace_id'                       => 15,
                    'currency'                             => 'HKD',
                    'origin_country'                       => 'hk',
                    'language_code'                        => 'zh_HK',
                    'is_multivariation'                    => 0,
                    'is_freight_shipping'                  => 0,
                    'is_calculated_shipping'               => 0,
                    'is_tax_table'                         => 0,
                    'is_vat'                               => 0,
                    'is_stp'                               => 0,
                    'is_stp_advanced'                      => 0,
                    'is_map'                               => 0,
                    'is_local_shipping_rate_table'         => 0,
                    'is_international_shipping_rate_table' => 0,
                    'is_english_measurement_system'        => 0,
                    'is_metric_measurement_system'         => 1,
                    'is_managed_payments'                  => 0,
                    'is_cash_on_delivery'                  => 0,
                    'is_global_shipping_program'           => 0,
                    'is_charity'                           => 1,
                    'is_in_store_pickup'                   => 0,
                    'is_return_description'                => 0,
                    'is_epid'                              => 0,
                    'is_ktype'                             => 0
                ],
                [
                    'marketplace_id'                       => 16,
                    'currency'                             => 'INR',
                    'origin_country'                       => 'in',
                    'language_code'                        => 'hi_IN',
                    'is_multivariation'                    => 1,
                    'is_freight_shipping'                  => 0,
                    'is_calculated_shipping'               => 0,
                    'is_tax_table'                         => 0,
                    'is_vat'                               => 1,
                    'is_stp'                               => 0,
                    'is_stp_advanced'                      => 0,
                    'is_map'                               => 0,
                    'is_local_shipping_rate_table'         => 0,
                    'is_international_shipping_rate_table' => 0,
                    'is_english_measurement_system'        => 0,
                    'is_metric_measurement_system'         => 1,
                    'is_managed_payments'                  => 0,
                    'is_cash_on_delivery'                  => 0,
                    'is_global_shipping_program'           => 0,
                    'is_charity'                           => 1,
                    'is_in_store_pickup'                   => 0,
                    'is_return_description'                => 0,
                    'is_epid'                              => 0,
                    'is_ktype'                             => 0
                ],
                [
                    'marketplace_id'                       => 17,
                    'currency'                             => 'EUR',
                    'origin_country'                       => 'ie',
                    'language_code'                        => 'en_IE',
                    'is_multivariation'                    => 1,
                    'is_freight_shipping'                  => 0,
                    'is_calculated_shipping'               => 0,
                    'is_tax_table'                         => 0,
                    'is_vat'                               => 1,
                    'is_stp'                               => 0,
                    'is_stp_advanced'                      => 0,
                    'is_map'                               => 0,
                    'is_local_shipping_rate_table'         => 0,
                    'is_international_shipping_rate_table' => 0,
                    'is_english_measurement_system'        => 0,
                    'is_metric_measurement_system'         => 1,
                    'is_managed_payments'                  => 0,
                    'is_cash_on_delivery'                  => 0,
                    'is_global_shipping_program'           => 0,
                    'is_charity'                           => 1,
                    'is_in_store_pickup'                   => 0,
                    'is_return_description'                => 0,
                    'is_epid'                              => 0,
                    'is_ktype'                             => 0
                ],
                [
                    'marketplace_id'                       => 18,
                    'currency'                             => 'MYR',
                    'origin_country'                       => 'my',
                    'language_code'                        => 'ms_MY',
                    'is_multivariation'                    => 1,
                    'is_freight_shipping'                  => 0,
                    'is_calculated_shipping'               => 0,
                    'is_tax_table'                         => 0,
                    'is_vat'                               => 0,
                    'is_stp'                               => 0,
                    'is_stp_advanced'                      => 0,
                    'is_map'                               => 0,
                    'is_local_shipping_rate_table'         => 0,
                    'is_international_shipping_rate_table' => 0,
                    'is_english_measurement_system'        => 0,
                    'is_metric_measurement_system'         => 1,
                    'is_managed_payments'                  => 0,
                    'is_cash_on_delivery'                  => 0,
                    'is_global_shipping_program'           => 0,
                    'is_charity'                           => 1,
                    'is_in_store_pickup'                   => 0,
                    'is_return_description'                => 0,
                    'is_epid'                              => 0,
                    'is_ktype'                             => 0
                ],
                [
                    'marketplace_id'                       => 19,
                    'currency'                             => 'CAD',
                    'origin_country'                       => 'ca',
                    'language_code'                        => 'fr_CA',
                    'is_multivariation'                    => 0,
                    'is_freight_shipping'                  => 1,
                    'is_calculated_shipping'               => 1,
                    'is_tax_table'                         => 1,
                    'is_vat'                               => 0,
                    'is_stp'                               => 1,
                    'is_stp_advanced'                      => 0,
                    'is_map'                               => 0,
                    'is_local_shipping_rate_table'         => 1,
                    'is_international_shipping_rate_table' => 1,
                    'is_english_measurement_system'        => 1,
                    'is_metric_measurement_system'         => 1,
                    'is_managed_payments'                  => 0,
                    'is_cash_on_delivery'                  => 0,
                    'is_global_shipping_program'           => 0,
                    'is_charity'                           => 1,
                    'is_in_store_pickup'                   => 0,
                    'is_return_description'                => 0,
                    'is_epid'                              => 0,
                    'is_ktype'                             => 0
                ],
                [
                    'marketplace_id'                       => 20,
                    'currency'                             => 'PHP',
                    'origin_country'                       => 'ph',
                    'language_code'                        => 'fil_PH',
                    'is_multivariation'                    => 1,
                    'is_freight_shipping'                  => 0,
                    'is_calculated_shipping'               => 0,
                    'is_tax_table'                         => 0,
                    'is_vat'                               => 0,
                    'is_stp'                               => 0,
                    'is_stp_advanced'                      => 0,
                    'is_map'                               => 0,
                    'is_local_shipping_rate_table'         => 0,
                    'is_international_shipping_rate_table' => 0,
                    'is_english_measurement_system'        => 0,
                    'is_metric_measurement_system'         => 1,
                    'is_managed_payments'                  => 0,
                    'is_cash_on_delivery'                  => 0,
                    'is_global_shipping_program'           => 0,
                    'is_charity'                           => 1,
                    'is_in_store_pickup'                   => 0,
                    'is_return_description'                => 0,
                    'is_epid'                              => 0,
                    'is_ktype'                             => 0
                ],
                [
                    'marketplace_id'                       => 21,
                    'currency'                             => 'PLN',
                    'origin_country'                       => 'pl',
                    'language_code'                        => 'pl_PL',
                    'is_multivariation'                    => 0,
                    'is_freight_shipping'                  => 0,
                    'is_calculated_shipping'               => 0,
                    'is_tax_table'                         => 0,
                    'is_vat'                               => 0,
                    'is_stp'                               => 0,
                    'is_stp_advanced'                      => 0,
                    'is_map'                               => 0,
                    'is_local_shipping_rate_table'         => 0,
                    'is_international_shipping_rate_table' => 0,
                    'is_english_measurement_system'        => 0,
                    'is_metric_measurement_system'         => 1,
                    'is_managed_payments'                  => 0,
                    'is_cash_on_delivery'                  => 0,
                    'is_global_shipping_program'           => 0,
                    'is_charity'                           => 1,
                    'is_in_store_pickup'                   => 0,
                    'is_return_description'                => 0,
                    'is_epid'                              => 0,
                    'is_ktype'                             => 0
                ],
                [
                    'marketplace_id'                       => 22,
                    'currency'                             => 'SGD',
                    'origin_country'                       => 'sg',
                    'language_code'                        => 'zh_SG',
                    'is_multivariation'                    => 0,
                    'is_freight_shipping'                  => 0,
                    'is_calculated_shipping'               => 0,
                    'is_tax_table'                         => 0,
                    'is_vat'                               => 0,
                    'is_stp'                               => 0,
                    'is_stp_advanced'                      => 0,
                    'is_map'                               => 0,
                    'is_local_shipping_rate_table'         => 0,
                    'is_international_shipping_rate_table' => 0,
                    'is_english_measurement_system'        => 0,
                    'is_metric_measurement_system'         => 1,
                    'is_managed_payments'                  => 0,
                    'is_cash_on_delivery'                  => 0,
                    'is_global_shipping_program'           => 0,
                    'is_charity'                           => 1,
                    'is_in_store_pickup'                   => 0,
                    'is_return_description'                => 0,
                    'is_epid'                              => 0,
                    'is_ktype'                             => 0
                ]
            ]
        );
    }

    private function installAmazon()
    {
        $moduleConfig = $this->getConfigModifier();

        $moduleConfig->insert('/amazon/', 'application_name', 'M2ePro - Amazon Magento Integration');
        $moduleConfig->insert('/component/amazon/', 'mode', '1');
        $moduleConfig->insert('/cron/task/amazon/listing/product/process_instructions/', 'mode', '1');
        $moduleConfig->insert('/cron/task/amazon/listing/synchronize_inventory/', 'interval_per_account', '86400');
        $moduleConfig->insert('/listing/product/inspector/amazon/', 'max_allowed_instructions_count', '2000');
        $moduleConfig->insert('/amazon/listing/product/instructions/cron/', 'listings_products_per_one_time', '1000');
        $moduleConfig->insert('/amazon/listing/product/action/scheduled_data/', 'limit', '20000');
        $moduleConfig->insert(
            '/amazon/listing/product/action/processing/prepare/',
            'max_listings_products_count',
            '2000'
        );
        $moduleConfig->insert('/amazon/listing/product/action/list/', 'min_allowed_wait_interval', '3600');
        $moduleConfig->insert('/amazon/listing/product/action/relist/', 'min_allowed_wait_interval', '1800');
        $moduleConfig->insert('/amazon/listing/product/action/revise_qty/', 'min_allowed_wait_interval', '900');
        $moduleConfig->insert('/amazon/listing/product/action/revise_price/', 'min_allowed_wait_interval', '1800');
        $moduleConfig->insert('/amazon/listing/product/action/revise_details/', 'min_allowed_wait_interval', '7200');
        $moduleConfig->insert('/amazon/listing/product/action/revise_images/', 'min_allowed_wait_interval', '7200');
        $moduleConfig->insert('/amazon/listing/product/action/stop/', 'min_allowed_wait_interval', '600');
        $moduleConfig->insert('/amazon/listing/product/action/delete/', 'min_allowed_wait_interval', '600');
        $moduleConfig->insert('/amazon/order/settings/marketplace_25/', 'use_first_street_line_as_company', '1');
        $moduleConfig->insert('/amazon/repricing/', 'mode', '1');
        $moduleConfig->insert('/amazon/repricing/', 'base_url', 'https://repricer.m2epro.com/connector/m2epro/');
        $moduleConfig->insert('/amazon/configuration/', 'business_mode', '0');

        $this->getConnection()->insertMultiple(
            $this->getFullTableName('marketplace'),
            [
                [
                    'id'             => 24,
                    'native_id'      => 4,
                    'title'          => 'Canada',
                    'code'           => 'CA',
                    'url'            => 'amazon.ca',
                    'status'         => 0,
                    'sorder'         => 4,
                    'group_title'    => 'America',
                    'component_mode' => 'amazon',
                    'update_date'    => '2013-05-08 00:00:00',
                    'create_date'    => '2013-05-08 00:00:00'
                ],
                [
                    'id'             => 25,
                    'native_id'      => 3,
                    'title'          => 'Germany',
                    'code'           => 'DE',
                    'url'            => 'amazon.de',
                    'status'         => 0,
                    'sorder'         => 3,
                    'group_title'    => 'Europe',
                    'component_mode' => 'amazon',
                    'update_date'    => '2013-05-08 00:00:00',
                    'create_date'    => '2013-05-08 00:00:00'
                ],
                [
                    'id'             => 26,
                    'native_id'      => 5,
                    'title'          => 'France',
                    'code'           => 'FR',
                    'url'            => 'amazon.fr',
                    'status'         => 0,
                    'sorder'         => 7,
                    'group_title'    => 'Europe',
                    'component_mode' => 'amazon',
                    'update_date'    => '2013-05-08 00:00:00',
                    'create_date'    => '2013-05-08 00:00:00'
                ],
                [
                    'id'             => 28,
                    'native_id'      => 2,
                    'title'          => 'United Kingdom',
                    'code'           => 'UK',
                    'url'            => 'amazon.co.uk',
                    'status'         => 0,
                    'sorder'         => 2,
                    'group_title'    => 'Europe',
                    'component_mode' => 'amazon',
                    'update_date'    => '2013-05-08 00:00:00',
                    'create_date'    => '2013-05-08 00:00:00'
                ],
                [
                    'id'             => 29,
                    'native_id'      => 1,
                    'title'          => 'United States',
                    'code'           => 'US',
                    'url'            => 'amazon.com',
                    'status'         => 0,
                    'sorder'         => 1,
                    'group_title'    => 'America',
                    'component_mode' => 'amazon',
                    'update_date'    => '2013-05-08 00:00:00',
                    'create_date'    => '2013-05-08 00:00:00'
                ],
                [
                    'id'             => 30,
                    'native_id'      => 7,
                    'title'          => 'Spain',
                    'code'           => 'ES',
                    'url'            => 'amazon.es',
                    'status'         => 0,
                    'sorder'         => 8,
                    'group_title'    => 'Europe',
                    'component_mode' => 'amazon',
                    'update_date'    => '2013-05-08 00:00:00',
                    'create_date'    => '2013-05-08 00:00:00'
                ],
                [
                    'id'             => 31,
                    'native_id'      => 8,
                    'title'          => 'Italy',
                    'code'           => 'IT',
                    'url'            => 'amazon.it',
                    'status'         => 0,
                    'sorder'         => 5,
                    'group_title'    => 'Europe',
                    'component_mode' => 'amazon',
                    'update_date'    => '2013-05-08 00:00:00',
                    'create_date'    => '2013-05-08 00:00:00'
                ],
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
                ],
                [
                    'id'             => 39,
                    'native_id'      => 11,
                    'title'          => 'Netherlands',
                    'code'           => 'NL',
                    'url'            => 'amazon.nl',
                    'status'         => 0,
                    'sorder'         => 12,
                    'group_title'    => 'Europe',
                    'component_mode' => 'amazon',
                    'update_date'    => '2020-03-26 00:00:00',
                    'create_date'    => '2020-03-26 00:00:00'
                ],
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
                ],
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
                ],
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
                ],
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
            ]
        );

        $this->getConnection()->insertMultiple(
            $this->getFullTableName('amazon_marketplace'),
            [
                [
                    'marketplace_id'                          => 24,
                    'developer_key'                           => '8636-1433-4377',
                    'default_currency'                        => 'CAD',
                    'is_new_asin_available'                   => 1,
                    'is_merchant_fulfillment_available'       => 0,
                    'is_business_available'                   => 0,
                    'is_vat_calculation_service_available'    => 0,
                    'is_product_tax_code_policy_available'    => 0,
                    'is_automatic_token_retrieving_available' => 1
                ],
                [
                    'marketplace_id'                          => 25,
                    'developer_key'                           => '7078-7205-1944',
                    'default_currency'                        => 'EUR',
                    'is_new_asin_available'                   => 1,
                    'is_merchant_fulfillment_available'       => 1,
                    'is_business_available'                   => 1,
                    'is_vat_calculation_service_available'    => 1,
                    'is_product_tax_code_policy_available'    => 1,
                    'is_automatic_token_retrieving_available' => 1
                ],
                [
                    'marketplace_id'                          => 26,
                    'developer_key'                           => '7078-7205-1944',
                    'default_currency'                        => 'EUR',
                    'is_new_asin_available'                   => 1,
                    'is_merchant_fulfillment_available'       => 0,
                    'is_business_available'                   => 1,
                    'is_vat_calculation_service_available'    => 1,
                    'is_product_tax_code_policy_available'    => 1,
                    'is_automatic_token_retrieving_available' => 1
                ],
                [
                    'marketplace_id'                          => 28,
                    'developer_key'                           => '7078-7205-1944',
                    'default_currency'                        => 'GBP',
                    'is_new_asin_available'                   => 1,
                    'is_merchant_fulfillment_available'       => 1,
                    'is_business_available'                   => 1,
                    'is_vat_calculation_service_available'    => 1,
                    'is_product_tax_code_policy_available'    => 1,
                    'is_automatic_token_retrieving_available' => 1
                ],
                [
                    'marketplace_id'                          => 29,
                    'developer_key'                           => '8636-1433-4377',
                    'default_currency'                        => 'USD',
                    'is_new_asin_available'                   => 1,
                    'is_merchant_fulfillment_available'       => 1,
                    'is_business_available'                   => 1,
                    'is_vat_calculation_service_available'    => 0,
                    'is_product_tax_code_policy_available'    => 0,
                    'is_automatic_token_retrieving_available' => 1
                ],
                [
                    'marketplace_id'                          => 30,
                    'developer_key'                           => '7078-7205-1944',
                    'default_currency'                        => 'EUR',
                    'is_new_asin_available'                   => 1,
                    'is_merchant_fulfillment_available'       => 0,
                    'is_business_available'                   => 1,
                    'is_vat_calculation_service_available'    => 1,
                    'is_product_tax_code_policy_available'    => 1,
                    'is_automatic_token_retrieving_available' => 1
                ],
                [
                    'marketplace_id'                          => 31,
                    'developer_key'                           => '7078-7205-1944',
                    'default_currency'                        => 'EUR',
                    'is_new_asin_available'                   => 1,
                    'is_merchant_fulfillment_available'       => 0,
                    'is_business_available'                   => 1,
                    'is_vat_calculation_service_available'    => 1,
                    'is_product_tax_code_policy_available'    => 1,
                    'is_automatic_token_retrieving_available' => 1
                ],
                [
                    'marketplace_id'                          => 34,
                    'developer_key'                           => '8636-1433-4377',
                    'default_currency'                        => 'MXN',
                    'is_new_asin_available'                   => 1,
                    'is_merchant_fulfillment_available'       => 0,
                    'is_business_available'                   => 0,
                    'is_vat_calculation_service_available'    => 0,
                    'is_product_tax_code_policy_available'    => 0,
                    'is_automatic_token_retrieving_available' => 1
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
                    'is_automatic_token_retrieving_available' => 1
                ],
                [
                    'marketplace_id'                          => 39,
                    'developer_key'                           => '7078-7205-1944',
                    'default_currency'                        => 'EUR',
                    'is_new_asin_available'                   => 1,
                    'is_merchant_fulfillment_available'       => 1,
                    'is_business_available'                   => 1,
                    'is_vat_calculation_service_available'    => 0,
                    'is_product_tax_code_policy_available'    => 1,
                    'is_automatic_token_retrieving_available' => 1
                ],
                [
                    'marketplace_id'                          => 40,
                    'developer_key'                           => '7078-7205-1944',
                    'default_currency'                        => 'TRY',
                    'is_new_asin_available'                   => 1,
                    'is_merchant_fulfillment_available'       => 1,
                    'is_business_available'                   => 0,
                    'is_vat_calculation_service_available'    => 0,
                    'is_product_tax_code_policy_available'    => 0,
                    'is_automatic_token_retrieving_available' => 1
                ],
                [
                    'marketplace_id'                          => 41,
                    'developer_key'                           => '7078-7205-1944',
                    'default_currency'                        => 'SEK',
                    'is_new_asin_available'                   => 1,
                    'is_merchant_fulfillment_available'       => 1,
                    'is_business_available'                   => 0,
                    'is_vat_calculation_service_available'    => 0,
                    'is_product_tax_code_policy_available'    => 0,
                    'is_automatic_token_retrieving_available' => 1
                ],
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
                ],
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
            ]
        );
    }

    private function installWalmart()
    {
        $moduleConfig = $this->getConfigModifier();

        $moduleConfig->insert('/walmart/', 'application_name', 'M2ePro - Walmart Magento Integration');
        $moduleConfig->insert('/component/walmart/', 'mode', '1');
        $moduleConfig->insert('/cron/task/walmart/listing/product/process_instructions/', 'mode', '1');
        $moduleConfig->insert('/cron/task/walmart/listing/synchronize_inventory/', 'interval_per_account', '86400');
        $moduleConfig->insert('/listing/product/inspector/walmart/', 'max_allowed_instructions_count', '2000');
        $moduleConfig->insert('/walmart/configuration/', 'sku_mode', '1');
        $moduleConfig->insert('/walmart/configuration/', 'sku_custom_attribute', null);
        $moduleConfig->insert('/walmart/configuration/', 'sku_modification_mode', '0');
        $moduleConfig->insert('/walmart/configuration/', 'sku_modification_custom_value', null);
        $moduleConfig->insert('/walmart/configuration/', 'generate_sku_mode', '0');
        $moduleConfig->insert('/walmart/configuration/', 'product_id_override_mode', '0', null);
        $moduleConfig->insert('/walmart/configuration/', 'upc_mode', '0');
        $moduleConfig->insert('/walmart/configuration/', 'upc_custom_attribute', null);
        $moduleConfig->insert('/walmart/configuration/', 'ean_mode', '0');
        $moduleConfig->insert('/walmart/configuration/', 'ean_custom_attribute', null);
        $moduleConfig->insert('/walmart/configuration/', 'gtin_mode', '0');
        $moduleConfig->insert('/walmart/configuration/', 'gtin_custom_attribute', null);
        $moduleConfig->insert('/walmart/configuration/', 'isbn_mode', '0');
        $moduleConfig->insert('/walmart/configuration/', 'isbn_custom_attribute', null);
        $moduleConfig->insert('/walmart/configuration/', 'option_images_url_mode', '0');
        $moduleConfig->insert('/walmart/order/settings/marketplace_25/', 'use_first_street_line_as_company', '1');
        $moduleConfig->insert('/walmart/listing/product/action/scheduled_data/', 'limit', '20000');
        $moduleConfig->insert('/walmart/listing/product/instructions/cron/', 'listings_products_per_one_time', '1000');
        $moduleConfig->insert('/walmart/listing/product/action/list/', 'min_allowed_wait_interval', '3600');
        $moduleConfig->insert('/walmart/listing/product/action/relist/', 'min_allowed_wait_interval', '1800');
        $moduleConfig->insert('/walmart/listing/product/action/revise_qty/', 'min_allowed_wait_interval', '900');
        $moduleConfig->insert('/walmart/listing/product/action/revise_price/', 'min_allowed_wait_interval', '1800');
        $moduleConfig->insert('/walmart/listing/product/action/revise_details/', 'min_allowed_wait_interval', '7200');
        $moduleConfig->insert('/walmart/listing/product/action/revise_lag_time/', 'min_allowed_wait_interval', '7200');
        $moduleConfig->insert('/walmart/listing/product/action/stop/', 'min_allowed_wait_interval', '600');
        $moduleConfig->insert('/walmart/listing/product/action/delete/', 'min_allowed_wait_interval', '600');
        $moduleConfig->insert(
            '/walmart/listing/product/action/processing/prepare/',
            'max_listings_products_count',
            '2000'
        );
        $moduleConfig->insert(
            '/walmart/listing/product/action/revise_promotions/',
            'min_allowed_wait_interval',
            '7200'
        );

        $this->getConnection()->insertMultiple(
            $this->getFullTableName('marketplace'),
            [
                [
                    'id'             => 37,
                    'native_id'      => 1,
                    'title'          => 'United States',
                    'code'           => 'US',
                    'url'            => 'walmart.com',
                    'status'         => 0,
                    'sorder'         => 3,
                    'group_title'    => 'America',
                    'component_mode' => 'walmart',
                    'update_date'    => '2013-05-08 00:00:00',
                    'create_date'    => '2013-05-08 00:00:00'
                ],
                [
                    'id'             => 38,
                    'native_id'      => 2,
                    'title'          => 'Canada',
                    'code'           => 'CA',
                    'url'            => 'walmart.ca',
                    'status'         => 0,
                    'sorder'         => 4,
                    'group_title'    => 'America',
                    'component_mode' => 'walmart',
                    'update_date'    => '2013-05-08 00:00:00',
                    'create_date'    => '2013-05-08 00:00:00'
                ],
            ]
        );

        $this->getConnection()->insertMultiple(
            $this->getFullTableName('walmart_marketplace'),
            [
                [
                    'marketplace_id'   => 37,
                    'developer_key'    => '8636-1433-4377',
                    'default_currency' => 'USD'
                ],
                [
                    'marketplace_id'   => 38,
                    'developer_key'    => '7078-7205-1944',
                    'default_currency' => 'CAD'
                ]
            ]
        );
    }

    //########################################

    private function isInstalled()
    {
        if (!$this->getConnection()->isTableExists($this->getFullTableName('setup'))) {
            return false;
        }

        $setupRow = $this->getConnection()->select()
            ->from($this->getFullTableName('setup'))
            ->where('version_from IS NULL')
            ->where('is_completed = ?', 1)
            ->query()
            ->fetch();

        return $setupRow !== false;
    }

    /**
     * @return \Ess\M2ePro\Model\Setup
     */
    private function getCurrentSetupObject()
    {
        return $this->activeRecordFactory->getObject('Setup')->getResource()->initCurrentSetupObject(
            null,
            $this->getConfigVersion()
        );
    }

    //########################################

    private function getConfigVersion()
    {
        return $this->moduleList->getOne(Module::IDENTIFIER)['setup_version'];
    }

    //########################################

    /**
     * @return \Magento\Framework\DB\Adapter\Pdo\Mysql
     */
    private function getConnection()
    {
        return $this->installer->getConnection();
    }

    private function getFullTableName($tableName)
    {
        return $this->helperFactory->getObject('Module_Database_Tables')->getFullName($tableName);
    }

    /**
     * @return Config
     */
    protected function getConfigModifier()
    {
        return $this->modelFactory->getObject(
            'Setup_Database_Modifier_Config',
            [
                'installer' => $this->installer,
                'tableName' => 'config',
            ]
        );
    }

    //########################################
}

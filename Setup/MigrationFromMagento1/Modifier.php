<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\MigrationFromMagento1;

use Magento\Framework\App\ResourceConnection;
use Magento\Setup\Module\Setup as MagentoSetup;

/**
 * Class \Ess\M2ePro\Setup\MigrationFromMagento1\Modifier
 */
class Modifier
{
    /** @var \Ess\M2ePro\Helper\Factory */
    protected $helperFactory;

    /** @var \Ess\M2ePro\Model\Factory */
    protected $modelFactory;

    /** @var MagentoSetup */
    protected $installer;

    //########################################

    public function __construct(
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        ResourceConnection $resourceConnection
    ) {
        $this->helperFactory = $helperFactory;
        $this->modelFactory  = $modelFactory;

        $this->installer = new MagentoSetup($resourceConnection);
    }

    //########################################

    /**
     * @return void
     */
    public function process(): void
    {
        $this->migrateConfig();
        $this->migrateWizards();
        $this->migrateWalmartAccount();
        $this->actualizeConfigs();
        $this->executeSomeFeatures();
    }

    //########################################

    private function migrateConfig()
    {
        $moduleConfig = $this->getConfigModifier();

        $moduleConfig->getEntity('/', 'is_disabled')->updateValue('0');

        $value = $moduleConfig->getEntity('/cron/service/', 'disabled')->getValue();
        $moduleConfig->insert('/cron/service_pub/', 'disabled', $value);
        $moduleConfig->getEntity('/cron/service/', 'disabled')->updateGroup('/cron/service_controller/');

        $moduleConfig->insert('/health_status/notification/', 'mode', 1);
        $moduleConfig->insert('/health_status/notification/', 'email', '');
        $moduleConfig->insert('/health_status/notification/', 'level', 40);

        $originalConfigTableName = $this->getFullTableName('config_m2_original');
        if (!$this->getConnection()->isTableExists($originalConfigTableName)) {
            $moduleConfig->update(
                'value',
                '02edcc129b6128f5fa52d4ad1202b427996122b6',
                ['`group` = ?' => '/server/', '`key`' => 'application_key']
            );
            $moduleConfig->update(
                'value',
                'https://marketplace.magento.com/m2e-ebay-amazon-magento2.html',
                ['`group` = ?' => '/support/', '`key`' => 'magento_marketplace_url']
            );

            return;
        }

        $migrateMap = [
            '/server/##application_key',
            '/license/##key',
            '/license/##status',
            '/license/info/##email',
            '/license/domain/##valid',
            '/license/domain/##real',
            '/license/domain/##is_valid',
            '/license/ip/##valid',
            '/license/ip/##real',
            '/license/ip/##is_valid',
            '/support/##magento_marketplace_url'
        ];

        $originalValues = $this->getConnection()
            ->select()
            ->from($originalConfigTableName)
            ->query()
            ->fetchAll();

        $config = $this->getConfigModifier();
        foreach ($originalValues as $original) {
            $key = "{$original['group']}##{$original['key']}";
            if (in_array($key, $migrateMap)) {
                $config->getEntity($original['group'], $original['key'])
                       ->updateValue($original['value']);
            }
        }
    }

    private function migrateWizards()
    {
        $this->getConnection()->truncateTable($this->getFullTableName('wizard'));

        $wizardsData = [
            [
                'nick'     => 'migrationFromMagento1',
                'view'     => '*',
                'status'   => 2,
                'step'     => null,
                'type'     => 1,
                'priority' => 1,
            ],
            [
                'nick'     => 'installationEbay',
                'view'     => 'ebay',
                'status'   => empty($this->getTableRows($this->getFullTableName('ebay_account')))
                    ? 0 : 2,
                'step'     => null,
                'type'     => 1,
                'priority' => 2,
            ],
            [
                'nick'     => 'installationAmazon',
                'view'     => 'amazon',
                'status'   => empty($this->getTableRows($this->getFullTableName('amazon_account')))
                    ? 0 : 2,
                'step'     => null,
                'type'     => 1,
                'priority' => 3,
            ],
            [
                'nick'     => 'installationWalmart',
                'view'     => 'walmart',
                'status'   => empty($this->getTableRows($this->getFullTableName('walmart_account')))
                    ? 0 : 2,
                'step'     => null,
                'type'     => 1,
                'priority' => 4,
            ],
            [
                'nick'     => 'migrationToInnodb',
                'view'     => '*',
                'status'   => 3,
                'step'     => null,
                'type'     => 1,
                'priority' => 5,
            ]
        ];

        $this->getConnection()->insertMultiple($this->getFullTableName('wizard'), $wizardsData);
    }

    /**
     * todo temporary
     */
    private function migrateWalmartAccount()
    {
        $this->getTableModifier('walmart_account')
            ->changeColumn('consumer_id', 'VARCHAR(255)', 'NULL', 'marketplace_id')
            ->renameColumn('old_private_key', 'private_key');
    }

    /**
     * @return void
     */
    private function actualizeConfigs()
    {
        $configModifier = $this->getConfigModifier();

        $documentationUrlEntity = $configModifier->getEntity('/support/', 'documentation_url');
        $oldUrl = $documentationUrlEntity->getValue();
        $newUrl = str_ends_with($oldUrl, '/') ?
            $oldUrl : "$oldUrl/";
        $documentationUrlEntity->updateValue($newUrl);
    }

    /**
     * @return void
     */
    private function executeSomeFeatures(): void
    {
        $featureClasses = [
            \Ess\M2ePro\Setup\Update\y22_m05\AddFeeColumnForEbayOrder::class,
            \Ess\M2ePro\Setup\Update\y22_m06\EbayFixedPriceModifier::class,
            \Ess\M2ePro\Setup\Update\y22_m07\AddEpidsForItaly::class,
            \Ess\M2ePro\Setup\Update\y22_m07\MoveEbayProductIdentifiers::class,
            \Ess\M2ePro\Setup\Update\y22_m08\MoveAmazonProductIdentifiers::class,
            \Ess\M2ePro\Setup\Update\y22_m10\AmazonWalmartSellingPolicyPriceModifier::class,
            \Ess\M2ePro\Setup\Update\y23_m01\EbayListingProductScheduledStopAction::class,
            \Ess\M2ePro\Setup\Update\y23_m01\AmazonProductTypes::class,
            \Ess\M2ePro\Setup\Update\y23_m02\AddTags::class,
            \Ess\M2ePro\Setup\Update\y23_m02\AddErrorCodeColumnForTags::class,
            \Ess\M2ePro\Setup\Update\y23_m02\AmazonShippingTemplates::class,
            \Ess\M2ePro\Setup\Update\y23_m02\AddImmediatePaymentColumn::class,
            \Ess\M2ePro\Setup\Update\y23_m03\UpgradeTags::class,
            \Ess\M2ePro\Setup\Update\y23_m03\AddWizardVersionDowngrade::class,
            \Ess\M2ePro\Setup\Update\y23_m04\RemoveUnavailableDataType::class,
            \Ess\M2ePro\Setup\Update\y23_m04\EbayBuyerInitiatedOrderCancellation::class,
            \Ess\M2ePro\Setup\Update\y23_m06\AddEbayBlockingErrorSetting::class,
            \Ess\M2ePro\Setup\Update\y23_m06\CreateProductTypeValidationTable::class,
            \Ess\M2ePro\Setup\Update\y23_m07\ChangeProductTypeValidationTableErrorMessageField::class,
            \Ess\M2ePro\Setup\Update\y23_m07\DropTemplateDescriptionIdIndex::class,
            \Ess\M2ePro\Setup\Update\y23_m07\RemoveScaleFromWatermarkSetting::class,
            \Ess\M2ePro\Setup\Update\y23_m07\ChangeDocumentationUrl::class,
            \Ess\M2ePro\Setup\Update\y23_m08\AddShippingIrregularForEbay::class,
            \Ess\M2ePro\Setup\Update\y23_m08\AddIsGetDeliveryPreferencesColumnToAmazonOrderTable::class,
            \Ess\M2ePro\Setup\Update\y23_m08\RemoveAmazonDescriptionPolicyRelatedData::class,
            \Ess\M2ePro\Setup\Update\y23_m08\CreateAmazonShippingMapTable::class,
            \Ess\M2ePro\Setup\Update\y23_m08\AddNewColumnsToAmazonOrder::class,
            \Ess\M2ePro\Setup\Update\y23_m08\AddAmazonSellingFormatListPrice::class,
            \Ess\M2ePro\Setup\Update\y23_m08\AddFinalFeesColumnToAmazonOrderTable::class,
            \Ess\M2ePro\Setup\Update\y23_m09\AddOnlineBestOfferForEbayProduct::class,
            \Ess\M2ePro\Setup\Update\y23_m09\RefactorAmazonOrderColumns::class,
            \Ess\M2ePro\Setup\Update\y23_m09\RemoveLastAccessAndRunFromConfigTable::class,
            \Ess\M2ePro\Setup\Update\y23_m09\AddAmazonProductTypeAttributeMappingTable::class,
            \Ess\M2ePro\Setup\Update\y23_m09\AddPriceRoundingToEbayAmazonWalmartSellingTemplate::class,
        ];

        foreach ($featureClasses as $featureClass) {
            /** @var \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature $feature */
            $feature = \Magento\Framework\App\ObjectManager::getInstance()->create($featureClass);
            $feature->execute();
        }
    }

    //########################################

    private function getConnection()
    {
        return $this->installer->getConnection();
    }

    private function getFullTableName($tableName)
    {
        return $this->helperFactory->getObject('Module_Database_Tables')->getFullName($tableName);
    }

    //########################################

    private function getTableRows($tableName)
    {
        $select = $this->getConnection()->select()->from($tableName);
        return $this->getConnection()->fetchAll($select);
    }

    /**
     * @param $tableName
     * @return \Ess\M2ePro\Model\Setup\Database\Modifier\Table
     */
    protected function getTableModifier($tableName)
    {
        return $this->modelFactory->getObject(
            'Setup_Database_Modifier_Table',
            [
                'installer' => $this->installer,
                'tableName' => $tableName,
            ]
        );
    }

    /**
     * @return \Ess\M2ePro\Model\Setup\Database\Modifier\Config
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
}

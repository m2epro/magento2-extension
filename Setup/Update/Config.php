<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Update;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractConfig;

/**
 * Class \Ess\M2ePro\Setup\Update\Config
 */
class Config extends AbstractConfig
{
    //########################################

    public function getFeaturesList()
    {
        return [
            'dev' => [
                'ChangeDevelopVersion',
                'ReinstallHookWithFix'
            ],
            'y19_m01' => [
                'NewUpgradesEngine',
                'AmazonOrdersUpdateDetails',
                'NewCronRunner'
            ],
            'y19_m04' => [
                'Walmart',
                'Maintenance',
                'WalmartAuthenticationForCA',
                'WalmartOptionImagesURL',
                'WalmartOrdersReceiveOn',
                'MigrationFromMagento1'
            ],
            'y19_m05' => [
                'WalmartAddMissingColumn'
            ],
            'y19_m07' => [
                'WalmartSynchAdvancedConditions'
            ],
            'y19_m10' => [
                'ConfigsNoticeRemoved',
                'RemoveAmazonShippingOverride',
                'NewSynchronization',
                'EnvironmentToConfigs',
                'CronTaskRemovedFromConfig',
                'EbayInStorePickup',
                'DropAutoMove',
                'Configs',
                'ProductVocabulary'
            ],
            'y19_m11' => [
                'AddEpidsAu',
                'RemoveListingOtherLog',
                'ProductsStatisticsImprovements',
                'WalmartProductIdOverride',
            ],
            'y19_m12' => [
                'RemoveReviseTotal',
                'RemoveEbayTranslation',
                'SynchDataFromM1',
                'RenameTableIndexerVariationParent',
                'WalmartReviseDescription',
                'EbayReturnPolicyM1'
            ],
            'y20_m01' => [
                'WebsitesActions',
                'FulfillmentCenter',
                'WalmartRemoveChannelUrl',
                'RemoveOutOfStockControl',
                'EbayLotSize',
                'EbayOrderUpdates'
            ],
            'y20_m02' => [
                'RepricingCount',
                'OrderNote',
                'ReviewPriorityCoefficients',
                'Configs'
            ],
            'y20_m03' => [
                'CronStrategy',
                'RemoveModePrefixFromChannelAccounts',
                'AmazonSendInvoice',
                'AmazonNL',
                'RemoveVersionsHistory'
            ],
            'y20_m04' => [
                'BrowsenodeIdFix'
            ],
            'y20_m05' => [
                'DisableUploadInvoicesAvailableNl'
            ]
        ];
    }

    //########################################

    public function getMultiRunFeaturesList()
    {
        return [];
    }

    //########################################
}

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
            'dev'     => [
                'ChangeDevelopVersion',
                'ReinstallHookWithFix',
                'PrimaryConfigs',
                'ModuleConfigs',
                'AddAmazonCollects',
                'AddGermanyInStorePickUpForDevelop',
                'DisableInStorePickUpCanadaFr'
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
                'RemoveVersionsHistory',
                'EbayCategories'
            ],
            'y20_m04' => [
                'SaveEbayCategory',
                'BrowsenodeIdFix'
            ],
            'y20_m05' => [
                'DisableUploadInvoicesAvailableNl',
                'Logs',
                'RemoveMagentoQtyRules',
                'RemovePriceDeviationRules',
                'PrimaryConfigs',
                'CacheConfigs',
                'ModuleConfigs',
                'ConvertIntoInnoDB'
            ],
            'y20_m06' => [
                'WalmartConsumerId',
                'RemoveCronDomains',
                'GeneralConfig',
                'EbayConfig',
                'AmazonConfig',
                'RefundShippingCost'
            ],
            'y20_m07' => [
                'EbayTemplateStoreCategory',
                'HashLongtextFields',
                'EbayTemplateCustomTemplateId',
                'WalmartKeywordsFields',
                'WalmartOrderItemQty'
            ],
            'y20_m08' => [
                'EbayManagedPayments',
                'GroupedProduct',
                'AmazonSkipTax',
                'AmazonTR',
                'VCSLiteInvoices'
            ],
            'y20_m09' => [
                'AmazonSE',
                'SellOnAnotherSite',
                'InventorySynchronization'
            ],
            'y20_m10' => [
                'ChangeSingleItemOption',
                'AddInvoiceAndShipment',
                'SellOnAnotherSite',
                'AddShipmentToAmazonListing',
                'AddGermanyInStorePickUp',
                'AddITCAShippingRateTable',
                'DefaultValuesInSyncPolicy'
            ],
            'y20_m11' => [
                'WalmartCustomCarrier',
                'RemoteFulfillmentProgram',
                'EbayRemoveCustomTemplates',
                'SynchronizeInventoryConfigs',
                'DisableVCSOnNL',
                'AmazonDuplicatedMarketplaceFeature',
                'AddSkipEvtinSetting',
                'EbayOrderCancelRefund'
            ],
            'y21_m01' => [
                'AmazonJP',
                'WalmartCancelRefundOption',
                'EbayRemoveClickAndCollect'
            ],
            'y21_m02' => [
                'MoveAUtoAsiaPacific',
                'AmazonPL',
                'EbayManagedPayments'
            ],
            'y21_m03' => [
                'IncludeeBayProductDetails',
                'EbayMotorsAddManagedPayments'
            ],
            'y21_m04' => [
                'AmazonRelistPrice',
                'AddShipByDate'
            ],
            'y21_m05' => [
                'EbayStoreCategoryIDs'
            ],
            'y21_m06' => [
                'FixBrokenUrl',
                'EbayTaxReference'
            ],
            'y21_m07' => [
                'AmazonIossNumber'
            ]
        ];
    }

    //########################################

    public function getMultiRunFeaturesList()
    {
        return [
            'y20_m07' => [
                'WalmartOrderItemQty'
            ],
        ];
    }

    //########################################
}

<?php

namespace Ess\M2ePro\Setup\Update;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractConfig;

class Config extends AbstractConfig
{
    /**
     * @return \string[][]
     */
    public function getFeaturesList(): array
    {
        return [
            'dev'     => [
                'ChangeDevelopVersion',
                'ReinstallHookWithFix',
                'PrimaryConfigs',
                'ModuleConfigs',
                'AddAmazonCollects',
            ],
            'y19_m01' => [
                'NewUpgradesEngine',
                'AmazonOrdersUpdateDetails',
                'NewCronRunner',
            ],
            'y19_m04' => [
                'Walmart',
                'Maintenance',
                'WalmartAuthenticationForCA',
                'WalmartOptionImagesURL',
                'WalmartOrdersReceiveOn',
                'MigrationFromMagento1',
            ],
            'y19_m05' => [
                'WalmartAddMissingColumn',
            ],
            'y19_m07' => [
                'WalmartSynchAdvancedConditions',
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
                'ProductVocabulary',
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
                'EbayReturnPolicyM1',
            ],
            'y20_m01' => [
                'WebsitesActions',
                'FulfillmentCenter',
                'WalmartRemoveChannelUrl',
                'RemoveOutOfStockControl',
                'EbayLotSize',
                'EbayOrderUpdates',
            ],
            'y20_m02' => [
                'RepricingCount',
                'OrderNote',
                'ReviewPriorityCoefficients',
                'Configs',
            ],
            'y20_m03' => [
                'CronStrategy',
                'RemoveModePrefixFromChannelAccounts',
                'AmazonSendInvoice',
                'AmazonNL',
                'RemoveVersionsHistory',
                'EbayCategories',
            ],
            'y20_m04' => [
                'SaveEbayCategory',
                'BrowsenodeIdFix',
            ],
            'y20_m05' => [
                'DisableUploadInvoicesAvailableNl',
                'Logs',
                'RemoveMagentoQtyRules',
                'RemovePriceDeviationRules',
                'PrimaryConfigs',
                'CacheConfigs',
                'ModuleConfigs',
                'ConvertIntoInnoDB',
            ],
            'y20_m06' => [
                'WalmartConsumerId',
                'RemoveCronDomains',
                'GeneralConfig',
                'EbayConfig',
                'AmazonConfig',
                'RefundShippingCost',
            ],
            'y20_m07' => [
                'EbayTemplateStoreCategory',
                'HashLongtextFields',
                'EbayTemplateCustomTemplateId',
                'WalmartKeywordsFields',
                'WalmartOrderItemQty',
            ],
            'y20_m08' => [
                'EbayManagedPayments',
                'GroupedProduct',
                'AmazonSkipTax',
                'AmazonTR',
                'VCSLiteInvoices',
            ],
            'y20_m09' => [
                'AmazonSE',
                'SellOnAnotherSite',
                'InventorySynchronization',
            ],
            'y20_m10' => [
                'ChangeSingleItemOption',
                'AddInvoiceAndShipment',
                'SellOnAnotherSite',
                'AddShipmentToAmazonListing',
                'AddGermanyInStorePickUp',
                'AddITCAShippingRateTable',
                'DefaultValuesInSyncPolicy',
            ],
            'y20_m11' => [
                'WalmartCustomCarrier',
                'RemoteFulfillmentProgram',
                'EbayRemoveCustomTemplates',
                'SynchronizeInventoryConfigs',
                'DisableVCSOnNL',
                'AmazonDuplicatedMarketplaceFeature',
                'AddSkipEvtinSetting',
                'EbayOrderCancelRefund',
            ],
            'y21_m01' => [
                'AmazonJP',
                'WalmartCancelRefundOption',
                'EbayRemoveClickAndCollect',
            ],
            'y21_m02' => [
                'MoveAUtoAsiaPacific',
                'AmazonPL',
                'EbayManagedPayments',
            ],
            'y21_m03' => [
                'IncludeeBayProductDetails',
                'EbayMotorsAddManagedPayments',
            ],
            'y21_m04' => [
                'AmazonRelistPrice',
                'AddShipByDate',
            ],
            'y21_m05' => [
                'EbayStoreCategoryIDs',
            ],
            'y21_m06' => [
                'FixBrokenUrl',
                'EbayTaxReference',
            ],
            'y21_m07' => [
                'AmazonIossNumber',
            ],
            'y21_m08' => [
                'FixedStuckedManualPriceRevise',
            ],
            'y21_m10' => [
                'UpdateWatermarkImage',
                'PartsCompatibilityImprovement',
            ],
            'y21_m11' => [
                'EbayAddVatMode',
            ],
            'y21_m12' => [
                'AmazonOrdersFbaStore',
            ],
            'y22_m01' => [
                'ChangeRegistryKey',
            ],
            'y22_m02' => [
                'RemoveForumUrl',
                'ImportTaxRegistrationId',
                'ChangeDocumentationUrl',
            ],
            'y22_m03' => [
                'SetPrecisionInVatRateColumns',
            ],
            'y22_m04' => [
                'RemoveUnnecessaryConfig',
            ],
            'y22_m05' => [
                'AmazonOrderCancellationNewFlow',
                'DropListingColumns',
                'RemoveEbayPayment',
                'AddFeeColumnForEbayOrder',
            ],
            'y22_m06' => [
                'FixMistakenConfigs',
                'EbayFixedPriceModifier',
                'WalmartOrderItemBuyerCancellation',
            ],
            'y22_m07' => [
                'AddEpidsForItaly',
                'FixFieldBuyerCancellationRequested',
                'AmazonAccountRemoveToken',
                'AmazonMarketplaceRemoveAutomaticTokenColumn',
                'MoveEbayProductIdentifiers',
                'FixRemovedPolicyInScheduledActions',
                'ClearPolicyLinkingToDeletedAccount',
            ],
            'y22_m08' => [
                'AddAmazonMarketplacesBrSgInAe',
                'FixDevKeyForJapanAmazonMarketplace',
                'ClearPartListingAdditionalData',
                'AddIsReplacementColumnToAmazonOrder',
                'AddAfnProductActualQty',
                'FixNullableGroupsInConfigs',
                'MoveAmazonProductIdentifiers',
            ],
            'y22_m09' => [
                'AddAmazonMarketplaceBelgium',
                'RemoveHitCounterFromEbayDescriptionPolicy',
                'AddWalmartCustomerOrderId',
                'UpdateConfigAttrSupportUrl',
                'AddIsCriticalErrorReceivedFlag',
            ],
            'y22_m10' => [
                'AddIsSoldByAmazonColumnToAmazonOrder',
                'AddRepricingAccountTokenValidityField',
                'UpdateAmazonMarketplace',
                'RemoveEpidsForAustralia',
                'RemoveWalmartLegacySettings',
                'RemovePickupInStoreTablesAndColumns',
                'AmazonWalmartSellingPolicyPriceModifier',
                'RemoveRepricingDisablingConfig',
            ],
            'y22_m11' => [
                'FixWalmartChildListingId',
            ],
            'y23_m01' => [
                'FixEbayQtyReservationDays',
                'ChangeRepricerBaseUrl',
                'WalmartTrackingDetails',
                'RemoveConfigConvertLinebreaks',
                'EbayListingProductScheduledStopAction',
                'UpdateConfigSupportUrl',
                'AmazonRemoveUnnecessaryData',
                'AmazonProductTypes',
            ],
            'y23_m02' => [
                'AddImmediatePaymentColumn',
                'AddTags',
                'AddErrorCodeColumnForTags',
                'AmazonShippingTemplates',
            ],
            'y23_m03' => [
                'WalmartProductIdentifiers',
                'RemoveLicenseStatus',
                'RenameClientsToAccounts',
                'AddColumnIsStoppedManuallyForAmazonAndWalmartProducts',
                'UpgradeTags',
                'AddWizardVersionDowngrade',
            ],
            'y23_m04' => [
                'SetIsVatEbayMarketplacePL',
                'ChangeTypeProductAddIds',
                'RemoveUnavailableDataType',
                'EbayBuyerInitiatedOrderCancellation',
                'UpdateEbayVatMode',
            ],
            'y23_m06' => [
                'RemoveBuildLastVersionFromRegistry',
                'RemoveWalmartInventoryWpid',
                'CreateProductTypeValidationTable',
                'IgnoreVariationMpnInResolverConfig',
                'AddEbayBlockingErrorSetting',
            ],
            'y23_m07' => [
                'ChangeProductTypeValidationTableErrorMessageField',
                'DropTemplateDescriptionIdIndex',
                'RemoveScaleFromWatermarkSetting',
                'ChangeDocumentationUrl',
            ],
            'y23_m08' => [
                'AddShippingIrregularForEbay',
                'AddIsGetDeliveryPreferencesColumnToAmazonOrderTable',
                'RemoveCashOnDelivery',
                'RemoveAmazonDescriptionPolicyRelatedData',
                'CreateAmazonShippingMapTable',
                'AddNewColumnsToAmazonOrder',
                'AddAmazonSellingFormatListPrice',
                'AddFinalFeesColumnToAmazonOrderTable',
            ],
            'y23_m09' => [
                'AddOnlineBestOfferForEbayProduct',
                'RefactorAmazonOrderColumns',
                'RemoveLastAccessAndRunFromConfigTable',
                'AddAmazonProductTypeAttributeMappingTable',
                'AddProductModeColumnToEbayListing',
                'AddPriceRoundingToEbayAmazonWalmartSellingTemplate',
            ],
            'y23_m10' => [
                'EnableAmazonShippingServiceForSomeMarketplaces',
                'AddProductTypeViewModeColumn',
                'ImproveAmazonOrderPrefixes',
                'EnableEbayShippingRate',
                'RenameSoldByAmazonSetting',
                'ReAddIsSoldByAmazonColumnToAmazonOrder',
                'CreateEbayCategorySpecificValidationResultTable',
            ],
            'y23_m11' => [
                'RemoveSupportUrlFromConfigTable',
                'AddWalmartIsWFS',
                'AddWalmartOrdersWfsLastSynchronization',
                'AddAmazonOriginalOrderIdColumn',
                'RestoreEpidsForAustralia',
            ],
            'y23_m12' => [
                'AddEbayBuyerReturnRequested',
                'AddProductTypeTitleColumn',
                'AddCustomizedInfoToAmazonItems',
                'AddAmazonMarketplaceSouthAfrica',
                'UpdateProductTypeTitleColumn',
                'AddCreateShipmentFbaOrdersColumn',
                'AddTecdocKtypesIt',
                'AddAmazonInventoryFbaFieldsInAmazonAccountTable',
            ],
            'y24_m01' => [
                'AddListingProductAdvancedFilterTable',
                'ImproveAutoUpdateEbayFinalFees',
            ],
            'y24_m02' => [
                'RemoveInstallationKeyFromConfigTable',
                'ChangeTypeCustomValueFieldOnCategorySpecificTable',
                'DisableAmazonMarketplaceWithoutAccounts',
                'CombineInactiveEbayProductStatuses',
                'RemoveEbayTradingToken',
                'AddReviseProductIdentifiersToEbaySyncTemplate',
                'CleanSettingsInConfigTable',
                'CombineInactiveProductStatuses',
            ],
            'y24_m03' => [
                'AddOnlineRegularMapPriceToAmazonListingProduct',
                'AddKtypesResolveAttemptColumn',
                'CreateAndFillAmazonAccountMerchantSettingTable',
            ],
            'y24_m05' => [
                'AddEbayPromotion',
            ],
            'y24_m06' => [
                'AddAmazonShippingPalletDelivery',
                'AddPriceLastUpdateDateColumnToEbayListingProductTable',
                'RemoveAuEpidsVisibleFromConfigTable',
                'RemoveEbayCharity',
                'AddAmazonMarketplaceSaudiArabia',
                'AddEbayBundleOptionMappingTable',
            ],
            'y24_m07' => [
                'AddProductIdentifiersSettingsForAmazonListing',
                'AddOfferImagesToAmazonListing',
                'EnableVatCalculationServiceForPolandAndSweden',
                'AddEbayVideo',
                'NewListingWizardTables',
            ],
            'y24_m08' => [
                'AddDateOfInvoiceSendingToAmazonOrder',
                'RemoveBlockingErrorsFromConfigTable',
                'UpdateAmazonDictionaryProductType'
            ],
            'y24_m09' => [
                'RemoveIsNewAsinAvailableFromAmazonMarketplace',
                'AddInternationalShippingRateTablesForAustralia',
                'RemoveUnusedAmazonTables',
                'AddWalmartProductTypes',
            ],
            'y24_m10' => [
                'DropTableWalmartDictionarySpecific',
                'EbayAccountAddSiteColumn',
                'AddAttributeMapping',
                'AddEbayComplianceDocuments',
            ],
            'y24_m11' => [
                'AddDeliveryDateFromColumnToAmazonOrder',
                'AddCustomValueToAttributeMapping',
                'AddAttributeOptionMapping',
            ],
            'y24_m12' => [
                'AddLanguageToEbayComplianceDocuments',
                'AddAmazonMarketplaceIreland',
                'ChangeColumnValueSizeInAttributeMappingTable',
            ],
            'y25_m01' => [
                'AddPaymentMethodDetailsColumnToAmazonOrder',
            ],
            'y25_m02' => [
                'DisableB2BForSomeAmazonMarketplaces',
                'AddConditionDescriptorIntoEbayDescriptionTemplate',
            ],
            'y25_m03' => [
                'DeleteTemplateDescriptionIdColumnFromAmazonListingProductTable',
                'AddCustomAttributeForConditionDescriptorIntoEbayDescriptionTemplate',
            ],
            'y25_m04' => [
                'AddConditionColumnsIntoWalmartListingTable',
            ],
        ];
    }

    /**
     * @return string[]
     */
    public static function getFeaturesForRepeatAfterMigrationFromMagento1(): array
    {
        return [
            \Ess\M2ePro\Setup\Update\y22_m05\AddFeeColumnForEbayOrder::class,
            \Ess\M2ePro\Setup\Update\y22_m06\EbayFixedPriceModifier::class,
            \Ess\M2ePro\Setup\Update\y22_m07\AddEpidsForItaly::class,
            \Ess\M2ePro\Setup\Update\y22_m07\MoveEbayProductIdentifiers::class,
            \Ess\M2ePro\Setup\Update\y22_m10\AmazonWalmartSellingPolicyPriceModifier::class,
            \Ess\M2ePro\Setup\Update\y23_m01\EbayListingProductScheduledStopAction::class,
            \Ess\M2ePro\Setup\Update\y23_m02\AddTags::class,
            \Ess\M2ePro\Setup\Update\y23_m02\AddErrorCodeColumnForTags::class,
            \Ess\M2ePro\Setup\Update\y23_m02\AmazonShippingTemplates::class,
            \Ess\M2ePro\Setup\Update\y23_m02\AddImmediatePaymentColumn::class,
            \Ess\M2ePro\Setup\Update\y23_m03\UpgradeTags::class,
            \Ess\M2ePro\Setup\Update\y23_m03\AddWizardVersionDowngrade::class,
            \Ess\M2ePro\Setup\Update\y23_m04\RemoveUnavailableDataType::class,
            \Ess\M2ePro\Setup\Update\y23_m04\EbayBuyerInitiatedOrderCancellation::class,
            \Ess\M2ePro\Setup\Update\y23_m06\AddEbayBlockingErrorSetting::class,
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
            \Ess\M2ePro\Setup\Update\y23_m09\AddProductModeColumnToEbayListing::class,
            \Ess\M2ePro\Setup\Update\y23_m09\AddOnlineBestOfferForEbayProduct::class,
            \Ess\M2ePro\Setup\Update\y23_m09\RefactorAmazonOrderColumns::class,
            \Ess\M2ePro\Setup\Update\y23_m09\RemoveLastAccessAndRunFromConfigTable::class,
            \Ess\M2ePro\Setup\Update\y23_m09\AddPriceRoundingToEbayAmazonWalmartSellingTemplate::class,
            \Ess\M2ePro\Setup\Update\y23_m10\CreateEbayCategorySpecificValidationResultTable::class,
            \Ess\M2ePro\Setup\Update\y23_m10\ImproveAmazonOrderPrefixes::class,
            \Ess\M2ePro\Setup\Update\y23_m10\RenameSoldByAmazonSetting::class,
            \Ess\M2ePro\Setup\Update\y23_m10\ReAddIsSoldByAmazonColumnToAmazonOrder::class,
            \Ess\M2ePro\Setup\Update\y23_m11\RemoveSupportUrlFromConfigTable::class,
            \Ess\M2ePro\Setup\Update\y23_m11\AddWalmartIsWFS::class,
            \Ess\M2ePro\Setup\Update\y23_m11\AddWalmartOrdersWfsLastSynchronization::class,
            \Ess\M2ePro\Setup\Update\y23_m11\AddAmazonOriginalOrderIdColumn::class,
            \Ess\M2ePro\Setup\Update\y23_m11\RestoreEpidsForAustralia::class,
            \Ess\M2ePro\Setup\Update\y23_m12\AddEbayBuyerReturnRequested::class,
            \Ess\M2ePro\Setup\Update\y23_m12\AddCustomizedInfoToAmazonItems::class,
            \Ess\M2ePro\Setup\Update\y23_m12\AddCreateShipmentFbaOrdersColumn::class,
            \Ess\M2ePro\Setup\Update\y23_m12\AddTecdocKtypesIt::class,
            \Ess\M2ePro\Setup\Update\y23_m12\AddAmazonInventoryFbaFieldsInAmazonAccountTable::class,

            \Ess\M2ePro\Setup\Update\y24_m01\AddListingProductAdvancedFilterTable::class,
            \Ess\M2ePro\Setup\Update\y24_m01\ImproveAutoUpdateEbayFinalFees::class,

            \Ess\M2ePro\Setup\Update\y24_m02\AddReviseProductIdentifiersToEbaySyncTemplate::class,
            \Ess\M2ePro\Setup\Update\y24_m02\DisableAmazonMarketplaceWithoutAccounts::class,

            \Ess\M2ePro\Setup\Update\y24_m03\AddOnlineRegularMapPriceToAmazonListingProduct::class,
            \Ess\M2ePro\Setup\Update\y24_m03\AddKtypesResolveAttemptColumn::class,
            \Ess\M2ePro\Setup\Update\y24_m03\CreateAndFillAmazonAccountMerchantSettingTable::class,

            \Ess\M2ePro\Setup\Update\y24_m05\AddEbayPromotion::class,

            \Ess\M2ePro\Setup\Update\y24_m06\AddAmazonShippingPalletDelivery::class,
            \Ess\M2ePro\Setup\Update\y24_m06\AddPriceLastUpdateDateColumnToEbayListingProductTable::class,
            \Ess\M2ePro\Setup\Update\y24_m06\RemoveAuEpidsVisibleFromConfigTable::class,
            \Ess\M2ePro\Setup\Update\y24_m06\AddEbayBundleOptionMappingTable::class,

            \Ess\M2ePro\Setup\Update\y24_m07\AddProductIdentifiersSettingsForAmazonListing::class,
            \Ess\M2ePro\Setup\Update\y24_m07\AddOfferImagesToAmazonListing::class,
            \Ess\M2ePro\Setup\Update\y24_m07\AddEbayVideo::class,

            \Ess\M2ePro\Setup\Update\y24_m08\RemoveBlockingErrorsFromConfigTable::class,

            \Ess\M2ePro\Setup\Update\y24_m07\NewListingWizardTables::class,

            \Ess\M2ePro\Setup\Update\y24_m10\AddEbayComplianceDocuments::class,
            \Ess\M2ePro\Setup\Update\y24_m10\AddAttributeMapping::class,

            \Ess\M2ePro\Setup\Update\y24_m11\AddAttributeOptionMapping::class,
            \Ess\M2ePro\Setup\Update\y24_m11\AddCustomValueToAttributeMapping::class,

            \Ess\M2ePro\Setup\Update\y24_m12\AddLanguageToEbayComplianceDocuments::class,
            \Ess\M2ePro\Setup\Update\y24_m12\ChangeColumnValueSizeInAttributeMappingTable::class,

            \Ess\M2ePro\Setup\Update\y25_m01\AddPaymentMethodDetailsColumnToAmazonOrder::class,

            \Ess\M2ePro\Setup\Update\y25_m02\DisableB2BForSomeAmazonMarketplaces::class,
            \Ess\M2ePro\Setup\Update\y25_m02\AddConditionDescriptorIntoEbayDescriptionTemplate::class,

            \Ess\M2ePro\Setup\Update\y25_m03\AddCustomAttributeForConditionDescriptorIntoEbayDescriptionTemplate::class,

            \Ess\M2ePro\Setup\Update\y25_m04\AddConditionColumnsIntoWalmartListingTable::class,
        ];
    }

    /**
     * @return \string[][]
     */
    public function getMultiRunFeaturesList(): array
    {
        return [
            'y20_m07' => [
                'WalmartOrderItemQty',
            ],
        ];
    }
}

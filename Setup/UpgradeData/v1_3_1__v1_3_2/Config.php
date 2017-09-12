<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\UpgradeData\v1_3_1__v1_3_2;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractConfig;

class Config extends AbstractConfig
{
    //########################################

    public function getFeaturesList()
    {
        return [
            'SearchSettingsDataCapacity',
            'HealthStatus',
            'UUIDRegenerate',
            'ArchivedEntity',
            'OrdersGridIndexes',
            'TransactionalLocks',
            'EbayKtypesSpain',
            'ActualizeRequirementsPopup',
            'IssuesResolverCronTask',
            'RemoveTerapeak',
            'PriceTypeConverting',
            'NewProductActions',
            'OtherListingProductUpdate',
            'AmazonShippingTemplateAttributes',
            'EbayEpidsDeUk',
            'EbayWasteRecyclingFee',
            'AmazonBusiness',
            'AmazonBusinessDataMigration',
            'AmazonSkusQueue',
            'DownloadableCustomType',
            'EbayOldItems',
            'NewFolderStructureForCronTasks',
            'AdditionalFieldsForListingProduct',
            'MovingSynchProductLimitToConfig',
            'OneCurrencyForCanada',
            'ActionConfigurator'
        ];
    }

    //########################################
}
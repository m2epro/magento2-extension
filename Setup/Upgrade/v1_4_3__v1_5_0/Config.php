<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Upgrade\v1_4_3__v1_5_0;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractConfig;

class Config extends AbstractConfig
{
    //########################################

    public function getFeaturesList()
    {
        return [
            '@y19_m10/ConfigsNoticeRemoved',
            '@y19_m10/RemoveAmazonShippingOverride',
            '@y19_m10/NewSynchronization',
            '@y19_m10/EnvironmentToConfigs',
            '@y19_m10/CronTaskRemovedFromConfig',
            '@y19_m10/EbayInStorePickup',
            '@y19_m10/DropAutoMove',
            '@y19_m10/Configs',
            '@y19_m10/ProductVocabulary',

            '@y19_m11/AddEpidsAu',
            '@y19_m11/RemoveListingOtherLog',

            '@y19_m12/RemoveReviseTotal',
            '@y19_m12/RemoveEbayTranslation',
            '@y19_m12/SynchDataFromM1',
            '@y19_m12/RenameTableIndexerVariationParent',
            '@y19_m12/WalmartReviseDescription',
            '@y19_m12/EbayReturnPolicyM1',

            '@y20_m01/WebsitesActions',
            '@y20_m01/FulfillmentCenter',
            '@y20_m01/WalmartRemoveChannelUrl',
            '@y20_m01/RemoveOutOfStockControl',
            '@y20_m01/EbayLotSize',
            '@y20_m01/EbayOrderUpdates',

            '@y20_m02/RepricingCount',
            '@y20_m02/OrderNote',
            '@y20_m02/ReviewPriorityCoefficients',
            '@y20_m02/Configs',
        ];
    }

    //########################################
}

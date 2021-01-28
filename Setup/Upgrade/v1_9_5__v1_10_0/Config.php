<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Upgrade\v1_9_5__v1_10_0;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractConfig;

class Config extends AbstractConfig
{
    //########################################

    public function getFeaturesList()
    {
        return [
            '@y20_m07/EbayTemplateCustomTemplateId',

            '@y20_m08/VCSLiteInvoices',

            '@y20_m10/AddInvoiceAndShipment',
            '@y20_m10/AddGermanyInStorePickUp',
            '@y20_m10/AddITCAShippingRateTable',
            '@y20_m10/AddShipmentToAmazonListing',
            '@y20_m10/DefaultValuesInSyncPolicy',

            '@y20_m11/EbayRemoveCustomTemplates',
            '@y20_m11/RemoteFulfillmentProgram',
            '@y20_m11/AddSkipEvtinSetting',
            '@y20_m11/DisableVCSOnNL',
            '@y20_m11/WalmartCustomCarrier'
        ];
    }

    //########################################
}

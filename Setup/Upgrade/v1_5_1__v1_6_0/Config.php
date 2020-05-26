<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Upgrade\v1_5_1__v1_6_0;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractConfig;

class Config extends AbstractConfig
{
    //########################################

    public function getFeaturesList()
    {
        return [
            '@y20_m03/CronStrategy',
            '@y20_m03/RemoveModePrefixFromChannelAccounts',
            '@y20_m03/AmazonSendInvoice',
            '@y20_m03/AmazonNL',
            '@y20_m03/RemoveVersionsHistory',

            '@y20_m04/BrowsenodeIdFix',

            '@y20_m05/DisableUploadInvoicesAvailableNl',
        ];
    }

    //########################################
}

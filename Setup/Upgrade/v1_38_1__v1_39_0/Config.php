<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Upgrade\v1_38_1__v1_39_0;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractConfig;

class Config extends AbstractConfig
{
    public function getFeaturesList(): array
    {
        return [
            '@y23_m03/UpgradeTags',
            '@y23_m03/AddWizardVersionDowngrade',
            '@y23_m04/SetIsVatEbayMarketplacePL',
            '@y23_m04/ChangeTypeProductAddIds',
            '@y23_m04/EbayBuyerInitiatedOrderCancellation',
            '@y23_m04/UpdateEbayVatMode',
        ];
    }
}

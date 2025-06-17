<?php

namespace Ess\M2ePro\Setup\Update\y19_m10;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

/**
 * Class \Ess\M2ePro\Setup\Update\y19_m10\EbayInStorePickup
 */
class EbayInStorePickup extends AbstractFeature
{
    //########################################

    public function execute()
    {
        $this->getConfigModifier('module')->delete('/ebay/in_store_pickup/', 'mode');
    }

    //########################################
}

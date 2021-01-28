<?php

namespace Ess\M2ePro\Setup\Update\y20_m11;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

/**
 * Class \Ess\M2ePro\Setup\Update\y20_m11\AddSkipEvtinSetting
 */
class AddSkipEvtinSetting extends AbstractFeature
{
    public function execute()
    {
        $this->getTableModifier('ebay_account')->addColumn(
            'skip_evtin',
            'TINYINT(2) UNSIGNED NOT NULL',
            0,
            'create_magento_shipment',
            false
        );
    }
}

<?php

namespace Ess\M2ePro\Setup\Update\y20_m10;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

/**
 * Class \Ess\M2ePro\Setup\Update\y20_m10\SellOnAnotherSite
 */
class SellOnAnotherSite extends AbstractFeature
{
    //########################################

    public function execute()
    {
        $this->getTableModifier('amazon_listing')->addColumn(
            'product_add_ids',
            'TEXT',
            'NULL',
            'restock_date_custom_attribute'
        );
    }

    //########################################
}

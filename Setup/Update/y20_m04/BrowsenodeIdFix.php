<?php

namespace Ess\M2ePro\Setup\Update\y20_m04;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

/**
 * Class \Ess\M2ePro\Setup\Update\y20_m04\BrowsenodeIdFix
 */
class BrowsenodeIdFix extends AbstractFeature
{
    //########################################

    public function execute()
    {
        $this->getTableModifier('amazon_dictionary_category_product_data')
            ->changeColumn(
                'browsenode_id',
                'DECIMAL(20, 0) UNSIGNED NOT NULL',
                null,
                'marketplace_id'
            );
    }

    //########################################
}

<?php

namespace Ess\M2ePro\Setup\Update\y19_m05;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

/**
 * Class \Ess\M2ePro\Setup\Update\y19\WalmartAddMissingColumn_m05
 */
class WalmartAddMissingColumn extends AbstractFeature
{
    //########################################

    public function execute()
    {
        $this->getTableModifier('walmart_indexer_listing_product_variation_parent')
            ->addColumn('component_mode', 'VARCHAR(10)', 'NULL', 'listing_id', true);
    }

    //########################################
}

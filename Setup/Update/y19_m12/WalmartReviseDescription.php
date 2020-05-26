<?php

namespace Ess\M2ePro\Setup\Update\y19_m12;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

/**
 * Class \Ess\M2ePro\Setup\Update\y19_m12\WalmartReviseDescription
 */
class WalmartReviseDescription extends AbstractFeature
{
    //########################################

    public function execute()
    {
        $this->getTableModifier('walmart_template_synchronization')
            ->addColumn(
                'revise_update_details',
                'SMALLINT(5) UNSIGNED NOT NULL',
                null,
                'revise_update_promotions',
                false,
                false
            )
            ->commit();

        $this->getTableModifier('walmart_listing_product')
            ->dropColumn('is_details_data_changed', true, false)
            ->commit();
    }

    //########################################
}

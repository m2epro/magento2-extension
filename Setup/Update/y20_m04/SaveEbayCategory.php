<?php

namespace Ess\M2ePro\Setup\Update\y20_m04;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

/**
 * Class \Ess\M2ePro\Setup\Update\y20_m04\SaveEbayCategory
 */
class SaveEbayCategory extends AbstractFeature
{
    //########################################

    public function execute()
    {
        $this->getTableModifier('ebay_listing_other')
            ->addColumn(
                'online_main_category',
                'VARCHAR(255)',
                'NULL',
                'online_bids',
                false,
                false
            )
            ->addColumn(
                'online_categories_data',
                'LONGTEXT',
                'NULL',
                'online_main_category',
                false,
                false
            )
            ->commit();
    }

    //########################################
}

<?php

namespace Ess\M2ePro\Setup\Update\y20_m01;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;
use Magento\Framework\DB\Ddl\Table;

/**
 * Class \Ess\M2ePro\Setup\Update\y20_m01\EbayLotSize
 */
class EbayLotSize extends AbstractFeature
{
    //########################################

    public function execute()
    {
        $this->getTableModifier('ebay_template_selling_format')
            ->addColumn(
                'lot_size_mode',
                'SMALLINT(5) UNSIGNED NOT NULL',
                0,
                'qty_max_posted_value',
                false,
                false
            )
            ->addColumn(
                'lot_size_custom_value',
                'INT(10) UNSIGNED',
                'NULL',
                'lot_size_mode',
                false,
                false
            )
            ->addColumn(
                'lot_size_attribute',
                'VARCHAR(255) NOT NULL',
                null,
                'lot_size_custom_value',
                false,
                false
            )
        ->commit();
    }

    //########################################
}

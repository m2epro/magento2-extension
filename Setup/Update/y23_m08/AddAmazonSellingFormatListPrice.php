<?php

namespace Ess\M2ePro\Setup\Update\y23_m08;

class AddAmazonSellingFormatListPrice extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    public function execute(): void
    {
        $modifier = $this->getTableModifier('amazon_template_selling_format');
        $modifier->addColumn(
            'regular_list_price_mode',
            'SMALLINT UNSIGNED NOT NULL',
            0,
            'regular_price_variation_mode',
            false,
            false
        );
        $modifier->addColumn(
            'regular_list_price_custom_attribute',
            'VARCHAR(255)',
            'NULL',
            'regular_list_price_mode',
            false,
            false
        );
        $modifier->commit();
    }
}

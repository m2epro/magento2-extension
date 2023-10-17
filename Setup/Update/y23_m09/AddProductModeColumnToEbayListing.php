<?php

namespace Ess\M2ePro\Setup\Update\y23_m09;

class AddProductModeColumnToEbayListing extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    public function execute(): void
    {
        $modifier = $this->getTableModifier('ebay_listing');
        $modifier->addColumn(
            'add_product_mode',
            'VARCHAR(10)',
            null,
            'auto_website_adding_template_store_category_secondary_id',
            false,
            false
        );
        $modifier->commit();
    }
}

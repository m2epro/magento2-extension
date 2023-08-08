<?php

namespace Ess\M2ePro\Setup\Update\y23_m08;

class AddShippingIrregularForEbay extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    public function execute(): void
    {
        $modifier = $this->getTableModifier('ebay_template_shipping');
        $modifier->addColumn(
            'shipping_irregular',
            'SMALLINT UNSIGNED NOT NULL',
            0,
            'local_shipping_discount_combined_profile_id',
            false,
            false
        );
        $modifier->commit();
    }
}

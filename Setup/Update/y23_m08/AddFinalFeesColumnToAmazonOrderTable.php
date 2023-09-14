<?php

namespace Ess\M2ePro\Setup\Update\y23_m08;

class AddFinalFeesColumnToAmazonOrderTable extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    public function execute(): void
    {
        $modifier = $this->getTableModifier('amazon_order');
        $modifier->addColumn(
            'final_fees',
            'TEXT',
            'NULL',
            'merchant_fulfillment_label',
            false,
            false
        );
        $modifier->commit();
    }
}

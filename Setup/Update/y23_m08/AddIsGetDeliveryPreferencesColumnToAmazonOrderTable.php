<?php

namespace Ess\M2ePro\Setup\Update\y23_m08;

class AddIsGetDeliveryPreferencesColumnToAmazonOrderTable extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    public function execute(): void
    {
        $modifier = $this->getTableModifier('amazon_order');
        $modifier->addColumn(
            'is_get_delivery_preferences',
            'SMALLINT UNSIGNED NOT NULL',
            0,
            'is_credit_memo_sent',
            false,
            false
        );
        $modifier->commit();
    }
}

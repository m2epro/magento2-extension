<?php

namespace Ess\M2ePro\Setup\Update\y23_m12;

class AddEbayBuyerReturnRequested extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    public function execute()
    {
        $modifier = $this->getTableModifier('ebay_order');
        $modifier->addColumn(
            'buyer_return_requested',
            'SMALLINT UNSIGNED NOT NULL',
            0,
            'buyer_cancellation_status'
        );
    }
}

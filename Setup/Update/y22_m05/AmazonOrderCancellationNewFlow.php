<?php

namespace Ess\M2ePro\Setup\Update\y22_m05;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

class AmazonOrderCancellationNewFlow extends AbstractFeature
{
    //########################################

    public function execute()
    {
        $this->getTableModifier('amazon_order')
             ->addColumn(
                 'is_buyer_requested_cancel',
                 'SMALLINT UNSIGNED NOT NULL',
                 0,
                 'tax_registration_id',
                 false,
                 false
             )
            ->addColumn(
                'buyer_cancel_reason',
                'TEXT',
                null,
                'is_buyer_requested_cancel',
                false,
                false
            )
             ->commit();
    }
}

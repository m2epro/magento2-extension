<?php

namespace Ess\M2ePro\Setup\Update\y19_m12;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

/**
 * Class \Ess\M2ePro\Setup\Update\y19_m12\RemoveReviseTotal
 */
class EbayReturnPolicyM1 extends AbstractFeature
{
    //########################################

    public function execute()
    {
        $this->getTableModifier('ebay_template_return_policy')
            ->addColumn(
                'international_accepted',
                'VARCHAR(255) NOT NULL',
                null,
                'shipping_cost',
                false,
                false
            )
            ->addColumn(
                'international_option',
                'VARCHAR(255) NOT NULL',
                null,
                'international_accepted',
                false,
                false
            )
            ->addColumn(
                'international_within',
                'VARCHAR(255) NOT NULL',
                null,
                'international_option',
                false,
                false
            )
            ->addColumn(
                'international_shipping_cost',
                'VARCHAR(255) NOT NULL',
                null,
                'international_within',
                false,
                false
            )
            ->dropColumn('holiday_mode', false, false)
            ->dropColumn('restocking_fee', false, false)
            ->commit();

        $this->getConnection()->update(
            $this->getFullTableName('ebay_template_return_policy'),
            [
                'international_accepted' => 'ReturnsNotAccepted'
            ]
        );
    }

    //########################################
}

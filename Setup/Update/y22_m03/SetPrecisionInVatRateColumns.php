<?php

namespace Ess\M2ePro\Setup\Update\y22_m03;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

class SetPrecisionInVatRateColumns extends AbstractFeature
{
    /**
     * @return void
     * @throws \Ess\M2ePro\Model\Exception\Setup
     */
    public function execute()
    {
        $this->getTableModifier('ebay_template_selling_format')
            ->changeColumn(
                'vat_percent',
                'decimal(10,2) unsigned not null',
                '0',
                null,
                false
            )
            ->commit();

        $this->getTableModifier('amazon_template_selling_format')
             ->changeColumn(
                 'regular_price_vat_percent',
                 'decimal(10,2) unsigned',
                 null,
                 null,
                 false
             )
            ->changeColumn(
                'business_price_vat_percent',
                'decimal(10,2) unsigned',
                null,
                null,
                false
            )
            ->commit();

        $this->getTableModifier('walmart_template_selling_format')
             ->changeColumn(
                 'price_vat_percent',
                 'decimal(10,2) unsigned',
                 null,
                 null,
                 false
             )
            ->commit();
    }
}

<?php

declare(strict_types=1);

namespace Ess\M2ePro\Setup\Update\y25_m10;

use Ess\M2ePro\Helper\Module\Database\Tables;

class RepricerMaxMinPriceUpdateWorkflow extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    public function execute(): void
    {
        $modifier = $this->getTableModifier($this->getFullTableName('amazon_account_repricing'));

        $modifier->addColumn(
            'min_price_value_attribute',
            'VARCHAR(255)',
            'NULL',
            'min_price_variation_mode',
            false,
            false
        );
        $modifier->addColumn(
            'min_price_percent_attribute',
            'VARCHAR(255)',
            'NULL',
            'min_price_value_attribute',
            false,
            false
        );
        $modifier->addColumn(
            'max_price_value_attribute',
            'VARCHAR(255)',
            'NULL',
            'max_price_variation_mode',
            false,
            false
        );
        $modifier->addColumn(
            'max_price_percent_attribute',
            'VARCHAR(255)',
            'NULL',
            'max_price_value_attribute',
            false,
            false
        );

        $modifier->commit();
    }
}

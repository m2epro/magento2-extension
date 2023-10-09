<?php

namespace Ess\M2ePro\Setup\Update\y23_m09;

use Magento\Framework\DB\Ddl\Table;

class AddPriceRoundingToEbayAmazonWalmartSellingTemplate extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    public function execute(): void
    {
        $this->addRoundingToEbayTemplate();
        $this->addRoundingToAmazonTemplate();
        $this->addRoundingToWalmartTemplate();
    }

    private function addRoundingToAmazonTemplate()
    {
        $modifier = $this->getTableModifier('amazon_template_selling_format');
        $modifier->addColumn(
            'price_rounding_option',
            'SMALLINT UNSIGNED NOT NULL',
            0,
            'regular_price_custom_attribute',
            false,
            false
        );
        $modifier->commit();
    }

    private function addRoundingToWalmartTemplate()
    {
        $modifier = $this->getTableModifier('walmart_template_selling_format');
        $modifier->addColumn(
            'price_rounding_option',
            'SMALLINT UNSIGNED NOT NULL',
            0,
            'price_custom_attribute',
            false,
            false
        );
        $modifier->commit();
    }

    private function addRoundingToEbayTemplate()
    {
        $columns = [
            'fixed_price_rounding_option' => 'fixed_price_custom_attribute',
            'start_price_rounding_option' => 'start_price_custom_attribute',
            'reserve_price_rounding_option' => 'reserve_price_custom_attribute',
            'buyitnow_price_rounding_option' => 'buyitnow_price_custom_attribute',
        ];

        foreach ($columns as $newColumn => $afterColumn) {
            $modifier = $this->getTableModifier('ebay_template_selling_format');
            $modifier->addColumn(
                $newColumn,
                'SMALLINT UNSIGNED NOT NULL',
                0,
                $afterColumn,
                false,
                false
            );
            $modifier->commit();
        }
    }
}

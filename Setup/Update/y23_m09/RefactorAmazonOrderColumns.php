<?php

namespace Ess\M2ePro\Setup\Update\y23_m09;

class RefactorAmazonOrderColumns extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    public function execute(): void
    {
        $this->modifyAmazonOrderTable();
        $this->updateShippingCategoryAndMapping();
    }

    private function modifyAmazonOrderTable()
    {
        $tableModifier = $this->getTableModifier('amazon_order');
        $tableModifier
            ->changeColumn(
                'shipping_category',
                'VARCHAR(255)',
                'NULL'
            )
            ->changeColumn(
                'shipping_mapping',
                'VARCHAR(255)',
                'NULL'
            );
        $tableModifier->commit();
    }

    private function updateShippingCategoryAndMapping()
    {
        $this->getConnection()->update(
            $this->getFullTableName('m2epro_amazon_order'),
            ['shipping_category' => null],
            [
                'shipping_category = ?' => 0,
            ]
        );

        $this->getConnection()->update(
            $this->getFullTableName('m2epro_amazon_order'),
            ['shipping_mapping' => null],
            [
                'shipping_mapping = ?' => 0,
            ]
        );
    }
}

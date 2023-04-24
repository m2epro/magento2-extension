<?php

namespace Ess\M2ePro\Setup\Update\y23_m03;

class AddColumnIsStoppedManuallyForAmazonAndWalmartProducts extends
    \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    /**
     * @return void
     * @throws \Ess\M2ePro\Model\Exception\Setup
     */
    public function execute(): void
    {
        $this->modifyTable('amazon_listing_product', 'is_general_id_owner');
        $this->modifyTable('walmart_listing_product', 'status_change_reasons');
    }

    /**
     * @param string $tableName
     * @param string $afterColumn
     *
     * @return void
     * @throws \Ess\M2ePro\Model\Exception\Setup
     */
    private function modifyTable(string $tableName, string $afterColumn): void
    {
        $tableModifier = $this->getTableModifier($tableName);
        $tableModifier->addColumn('is_stopped_manually', 'SMALLINT UNSIGNED NOT NULL', 0, $afterColumn);
    }
}

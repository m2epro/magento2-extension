<?php

declare(strict_types=1);

namespace Ess\M2ePro\Setup\Update\y25_m04;

class AddConditionColumnsIntoWalmartListingTable extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    public function execute()
    {
        $this->getTableModifier(\Ess\M2ePro\Helper\Module\Database\Tables::TABLE_WALMART_LISTING)
             ->addColumn(
                 \Ess\M2ePro\Model\ResourceModel\Walmart\Listing::COLUMN_CONDITION_MODE,
                 'INT UNSIGNED',
                 0,
                 \Ess\M2ePro\Model\ResourceModel\Walmart\Listing::COLUMN_TEMPLATE_SYNCHRONIZATION_ID,
                 false,
                 false
             )->addColumn(
                \Ess\M2ePro\Model\ResourceModel\Walmart\Listing::COLUMN_CONDITION_CUSTOM_ATTRIBUTE,
                'VARCHAR(255)',
                null,
                \Ess\M2ePro\Model\ResourceModel\Walmart\Listing::COLUMN_CONDITION_MODE,
                false,
                false
            )->addColumn(
                \Ess\M2ePro\Model\ResourceModel\Walmart\Listing::COLUMN_CONDITION_VALUE,
                'VARCHAR(255)',
                null,
                \Ess\M2ePro\Model\ResourceModel\Walmart\Listing::COLUMN_CONDITION_CUSTOM_ATTRIBUTE,
                false,
                false
            )->commit();
    }
}

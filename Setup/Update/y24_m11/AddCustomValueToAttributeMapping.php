<?php

declare(strict_types=1);

namespace Ess\M2ePro\Setup\Update\y24_m11;

use Ess\M2ePro\Helper\Module\Database\Tables;

class AddCustomValueToAttributeMapping extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    public function execute()
    {
        $modifier = $this->getTableModifier(Tables::TABLE_ATTRIBUTE_MAPPING);

        $modifier->addColumn(
            \Ess\M2ePro\Model\ResourceModel\AttributeMapping\Pair::COLUMN_VALUE_MODE,
            'INT NOT NULL',
            0,
            \Ess\M2ePro\Model\ResourceModel\AttributeMapping\Pair::COLUMN_CHANNEL_ATTRIBUTE_CODE,
            false,
            false
        );

        $modifier->renameColumn(
            'magento_attribute_code',
            \Ess\M2ePro\Model\ResourceModel\AttributeMapping\Pair::COLUMN_VALUE,
            false,
            false
        );
        $modifier->commit();

        $this->getConnection()->update(
            $this->getFullTableName(Tables::TABLE_ATTRIBUTE_MAPPING),
            [\Ess\M2ePro\Model\ResourceModel\AttributeMapping\Pair::COLUMN_VALUE_MODE => 1]
        );
    }
}

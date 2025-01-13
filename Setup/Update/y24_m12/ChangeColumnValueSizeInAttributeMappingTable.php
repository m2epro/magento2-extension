<?php

declare(strict_types=1);

namespace Ess\M2ePro\Setup\Update\y24_m12;

use Ess\M2ePro\Helper\Module\Database\Tables;

class ChangeColumnValueSizeInAttributeMappingTable extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    public function execute()
    {
        $modifier = $this->getTableModifier(Tables::TABLE_ATTRIBUTE_MAPPING);

        $modifier->changeColumn(
            \Ess\M2ePro\Model\ResourceModel\AttributeMapping\Pair::COLUMN_VALUE,
            'TEXT NOT NULL',
            null,
            null,
            false
        );

        $modifier->commit();
    }
}

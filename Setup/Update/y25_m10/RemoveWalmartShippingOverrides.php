<?php

declare(strict_types=1);

namespace Ess\M2ePro\Setup\Update\y25_m10;

use Ess\M2ePro\Helper\Module\Database\Tables;

class RemoveWalmartShippingOverrides extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    public function execute(): void
    {
        $this->dropColumns();
        $this->dropTable();
    }

    private function dropColumns(): void
    {
        $modifier = $this->getTableModifier(Tables::TABLE_WALMART_TEMPLATE_SELLING_FORMAT);

        $modifier->dropColumn('shipping_override_rule_mode', true, false);

        $modifier->commit();
    }

    private function dropTable(): void
    {
        $tableName = $this->getFullTableName('walmart_template_selling_format_shipping_override');

        $this->getConnection()->dropTable($tableName);
    }
}

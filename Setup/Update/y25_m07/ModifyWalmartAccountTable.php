<?php

declare(strict_types=1);

namespace Ess\M2ePro\Setup\Update\y25_m07;

use Ess\M2ePro\Helper\Module\Database\Tables;
use Ess\M2ePro\Model\ResourceModel\Walmart\Account as AccountResource;

class ModifyWalmartAccountTable extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    public function execute(): void
    {
        $this->addColumnToAccountTable();
        $this->removeColumnsFromAccountTable();
    }

    private function addColumnToAccountTable(): void
    {
        $modifier = $this->getTableModifier(Tables::TABLE_WALMART_ACCOUNT);

        $modifier->addColumn(
            AccountResource::COLUMN_IDENTIFIER,
            'VARCHAR(100) NOT NULL',
            '',
            AccountResource::COLUMN_MARKETPLACE_ID,
            false,
            false
        );

        $modifier->commit();
    }

    private function removeColumnsFromAccountTable(): void
    {
        $modifier = $this->getTableModifier(Tables::TABLE_WALMART_ACCOUNT);
        $modifier->dropColumn('consumer_id');
        $modifier->dropColumn('private_key');
        $modifier->dropColumn('client_id');
        $modifier->dropColumn('client_secret');
    }
}

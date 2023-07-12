<?php

namespace Ess\M2ePro\Setup\Update\y23_m07;

use Magento\Framework\DB\Ddl\Table;

class ChangeProductTypeValidationTableErrorMessageField extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    public function execute(): void
    {
        $tableModifier = $this->getTableModifier('amazon_product_type_validation');
        $tableModifier
            ->changeColumn(
                'error_messages',
                Table::TYPE_TEXT,
                null,
                'status',
                false
            );
        $tableModifier->commit();
    }
}

<?php

namespace Ess\M2ePro\Setup\Update\y23_m10;

use Magento\Framework\DB\Ddl\Table;

class AddProductTypeViewModeColumn extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    public function execute(): void
    {
        $modifier = $this->getTableModifier('amazon_template_product_type');
        $modifier->addColumn(
            'view_mode',
            'SMALLINT UNSIGNED NOT NULL',
            0,
            'id',
            false,
            false
        );
        $modifier->commit();
    }
}

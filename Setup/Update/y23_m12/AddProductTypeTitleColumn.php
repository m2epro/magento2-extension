<?php

namespace Ess\M2ePro\Setup\Update\y23_m12;

use Magento\Framework\DB\Ddl\Table;

class AddProductTypeTitleColumn extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    public function execute(): void
    {
        $modifier = $this->getTableModifier('amazon_template_product_type');
        $modifier->addColumn(
            'title',
            'VARCHAR(255)',
            null,
            'id',
            true
        );

        $modifier->commit();
    }
}

<?php

namespace Ess\M2ePro\Setup\Update\y23_m07;

use Magento\Framework\DB\Ddl\Table;

class DropTemplateDescriptionIdIndex extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    public function execute(): void
    {
        $this
            ->getTableModifier('amazon_listing_product')
            ->dropIndex('template_description_id', false)
            ->addIndex('template_product_type_id', false)
            ->commit();
    }
}

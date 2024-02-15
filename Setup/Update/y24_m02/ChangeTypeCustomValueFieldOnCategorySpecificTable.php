<?php

declare(strict_types=1);

namespace Ess\M2ePro\Setup\Update\y24_m02;

class ChangeTypeCustomValueFieldOnCategorySpecificTable extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    /**
     * @throws \Ess\M2ePro\Model\Exception\Setup
     */
    public function execute()
    {
        $this->getTableModifier('ebay_template_category_specific')
             ->changeColumn('value_custom_value', 'TEXT');
    }
}

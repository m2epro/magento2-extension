<?php

declare(strict_types=1);

namespace Ess\M2ePro\Setup\Update\y25_m02;

use Ess\M2ePro\Model\ResourceModel\Ebay\Template\Description;

class AddConditionDescriptorIntoEbayDescriptionTemplate extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    public function execute()
    {
        $this->getTableModifier(\Ess\M2ePro\Helper\Module\Database\Tables::TABLE_EBAY_TEMPLATE_DESCRIPTION)
             ->addColumn(
                 Description::COLUMN_CONDITION_PROFESSIONAL_GRADER_ID,
                 'INT UNSIGNED',
                 'NULL',
                 Description::COLUMN_CONDITION_NOTE_TEMPLATE,
                 false,
                 false
             )->addColumn(
                Description::COLUMN_CONDITION_GRADE_ID,
                'INT UNSIGNED',
                'NULL',
                Description::COLUMN_CONDITION_PROFESSIONAL_GRADER_ID,
                false,
                false
            )->addColumn(
                Description::COLUMN_CONDITION_GRADE_CERTIFICATION_NUMBER,
                'VARCHAR(255)',
                null,
                Description::COLUMN_CONDITION_PROFESSIONAL_GRADER_ID,
                false,
                false
            )->addColumn(
                Description::COLUMN_CONDITION_GRADE_CARD_CONDITION_ID,
                'INT UNSIGNED',
                'NULL',
                Description::COLUMN_CONDITION_GRADE_CERTIFICATION_NUMBER,
                false,
                false
            )->commit();
    }
}

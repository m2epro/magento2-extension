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
                 'condition_professional_grader_id',
                 'INT UNSIGNED',
                 'NULL',
                 Description::COLUMN_CONDITION_NOTE_TEMPLATE,
                 false,
                 false
             )->addColumn(
                'condition_grade_id',
                'INT UNSIGNED',
                'NULL',
                'condition_professional_grader_id',
                false,
                false
            )->addColumn(
                'condition_grade_certification_number',
                'VARCHAR(255)',
                null,
                'condition_professional_grader_id',
                false,
                false
            )->addColumn(
                'condition_grade_card_condition_id',
                'INT UNSIGNED',
                'NULL',
                'condition_grade_certification_number',
                false,
                false
            )->commit();
    }
}

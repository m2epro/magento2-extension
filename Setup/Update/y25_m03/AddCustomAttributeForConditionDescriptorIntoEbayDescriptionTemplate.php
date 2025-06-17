<?php

declare(strict_types=1);

namespace Ess\M2ePro\Setup\Update\y25_m03;

use Ess\M2ePro\Model\ResourceModel\Ebay\Template\Description;

class AddCustomAttributeForConditionDescriptorIntoEbayDescriptionTemplate extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    /** @see \Ess\M2ePro\Model\Ebay\Template\Description */
    private const CONDITION_DESCRIPTOR_MODE_NONE = 0;
    private const CONDITION_DESCRIPTOR_MODE_EBAY = 1;
    private const CONDITION_DESCRIPTOR_MODE_CUSTOM = 2;

    public function execute()
    {
        $this->modifyScheme();
        $this->installModeEbayOrCustomForNotEmptyValues();
    }

    private function modifyScheme(): void
    {
        $columnNamesMap = [
            'condition_professional_grader_id' => [
                'mode' => Description::COLUMN_CONDITION_PROFESSIONAL_GRADER_ID_MODE,
                'attr' => Description::COLUMN_CONDITION_PROFESSIONAL_GRADER_ID_ATTRIBUTE,
                'val' => Description::COLUMN_CONDITION_PROFESSIONAL_GRADER_ID_VALUE,
            ],
            'condition_grade_id' => [
                'mode' => Description::COLUMN_CONDITION_GRADE_ID_MODE,
                'attr' => Description::COLUMN_CONDITION_GRADE_ID_ATTRIBUTE,
                'val' => Description::COLUMN_CONDITION_GRADE_ID_VALUE,
            ],
            'condition_grade_certification_number' => [
                'mode' => Description::COLUMN_CONDITION_GRADE_CERTIFICATION_NUMBER_MODE,
                'attr' => Description::COLUMN_CONDITION_GRADE_CERTIFICATION_NUMBER_ATTRIBUTE,
                'val' => Description::COLUMN_CONDITION_GRADE_CERTIFICATION_NUMBER_CUSTOM_VALUE,
            ],
            'condition_grade_card_condition_id' => [
                'mode' => Description::COLUMN_CONDITION_GRADE_CARD_CONDITION_ID_MODE,
                'attr' => Description::COLUMN_CONDITION_GRADE_CARD_CONDITION_ID_ATTRIBUTE,
                'val' => Description::COLUMN_CONDITION_GRADE_CARD_CONDITION_ID_VALUE,
            ],
        ];

        foreach ($columnNamesMap as $currentColumnName => $newColumnNames) {
            $this->changeScheme(
                $currentColumnName,
                $newColumnNames['mode'],
                $newColumnNames['attr'],
                $newColumnNames['val']
            );
        }
    }

    private function changeScheme(
        string $currentValue,
        string $mode,
        string $attribute,
        string $newValue
    ): void {
        $modifier = $this->getTableModifier(
            \Ess\M2ePro\Helper\Module\Database\Tables::TABLE_EBAY_TEMPLATE_DESCRIPTION
        );

        if (!$modifier->isColumnExists($currentValue)) {
            return;
        }

        $modifier->renameColumn(
            $currentValue,
            $newValue,
            false,
            false
        )->commit();

        $modifier->addColumn(
            $mode,
            'SMALLINT UNSIGNED NOT NULL',
            self::CONDITION_DESCRIPTOR_MODE_NONE,
            $newValue
        );
        $modifier->addColumn(
            $attribute,
            'VARCHAR(255)',
            null,
            $mode
        );
    }

    private function installModeEbayOrCustomForNotEmptyValues(): void
    {
        $columnNames = [
            [
                'mode' => Description::COLUMN_CONDITION_PROFESSIONAL_GRADER_ID_MODE,
                'mode_value' => self::CONDITION_DESCRIPTOR_MODE_EBAY,
                'value' => Description::COLUMN_CONDITION_PROFESSIONAL_GRADER_ID_VALUE,
            ],
            [
                'mode' => Description::COLUMN_CONDITION_GRADE_ID_MODE,
                'mode_value' => self::CONDITION_DESCRIPTOR_MODE_EBAY,
                'value' => Description::COLUMN_CONDITION_GRADE_ID_VALUE,
            ],
            [
                'mode' => Description::COLUMN_CONDITION_GRADE_CERTIFICATION_NUMBER_MODE,
                'mode_value' => self::CONDITION_DESCRIPTOR_MODE_CUSTOM,
                'value' => Description::COLUMN_CONDITION_GRADE_CERTIFICATION_NUMBER_CUSTOM_VALUE,
            ],
            [
                'mode' => Description::COLUMN_CONDITION_GRADE_CARD_CONDITION_ID_MODE,
                'mode_value' => self::CONDITION_DESCRIPTOR_MODE_EBAY,
                'value' => Description::COLUMN_CONDITION_GRADE_CARD_CONDITION_ID_VALUE,
            ],
        ];

        $table = $this->getFullTableName(
            \Ess\M2ePro\Helper\Module\Database\Tables::TABLE_EBAY_TEMPLATE_DESCRIPTION
        );

        foreach ($columnNames as $columnName) {
            $mode = $columnName['mode'];
            $modeValue = $columnName['mode_value'];
            $value = $columnName['value'];

            $sql = <<<SQL
UPDATE `$table`
SET `$mode` = $modeValue
WHERE `$value` IS NOT NULL;
SQL;

            $this->getConnection()->query($sql);
        }
    }
}

<?php

declare(strict_types=1);

namespace Ess\M2ePro\Setup\Update\y26_m05;

use Ess\M2ePro\Helper\Module\Database\Tables;

class AddEbayCoinGrades extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    public function execute(): void
    {
        $modifier = $this->getTableModifier(Tables::TABLE_EBAY_TEMPLATE_DESCRIPTION);

        $modifier->addColumn(
            \Ess\M2ePro\Model\ResourceModel\Ebay\Template\Description::COLUMN_CONDITION_GRADE_LETTER_ID_VALUE,
            'INT UNSIGNED',
            null,
            \Ess\M2ePro\Model\ResourceModel\Ebay\Template\Description::COLUMN_CONDITION_GRADE_ID_ATTRIBUTE,
            false,
            false
        );

        $modifier->addColumn(
            \Ess\M2ePro\Model\ResourceModel\Ebay\Template\Description::COLUMN_CONDITION_GRADE_LETTER_ID_MODE,
            'SMALLINT UNSIGNED NOT NULL',
            '0',
            \Ess\M2ePro\Model\ResourceModel\Ebay\Template\Description::COLUMN_CONDITION_GRADE_LETTER_ID_VALUE,
            false,
            false
        );

        $modifier->addColumn(
            \Ess\M2ePro\Model\ResourceModel\Ebay\Template\Description::COLUMN_CONDITION_GRADE_LETTER_ID_ATTRIBUTE,
            'VARCHAR(255)',
            null,
            \Ess\M2ePro\Model\ResourceModel\Ebay\Template\Description::COLUMN_CONDITION_GRADE_LETTER_ID_MODE,
            false,
            false
        );

        $modifier->commit();
    }
}

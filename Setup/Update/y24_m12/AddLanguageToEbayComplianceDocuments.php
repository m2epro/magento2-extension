<?php

declare(strict_types=1);

namespace Ess\M2ePro\Setup\Update\y24_m12;

use Ess\M2ePro\Helper\Module\Database\Tables;

class AddLanguageToEbayComplianceDocuments extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    public function execute()
    {
        $modifier = $this->getTableModifier(Tables::TABLE_EBAY_COMPLIANCE_DOCUMENTS);

        $modifier->addColumn(
            \Ess\M2ePro\Model\ResourceModel\Ebay\ComplianceDocuments::COLUMN_LANGUAGES,
            'TEXT',
            null,
            null,
            false,
            false
        );

        $modifier->commit();
    }
}

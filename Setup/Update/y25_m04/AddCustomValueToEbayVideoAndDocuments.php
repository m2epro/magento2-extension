<?php

declare(strict_types=1);

namespace Ess\M2ePro\Setup\Update\y25_m04;

use Ess\M2ePro\Helper\Module\Database\Tables;
use Ess\M2ePro\Model\Ebay\Template\Description;
use Ess\M2ePro\Model\ResourceModel\Ebay\Template\Description as DescriptionResource;

class AddCustomValueToEbayVideoAndDocuments extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    public function execute()
    {
        $this->addColumnVideoCustomValueToEbayDescriptionTemplate();
        $this->changeComplianceDocumentConfig();
    }

    private function addColumnVideoCustomValueToEbayDescriptionTemplate()
    {
        $modifier = $this->getTableModifier(Tables::TABLE_EBAY_TEMPLATE_DESCRIPTION);

        $modifier->addColumn(
            DescriptionResource::COLUMN_VIDEO_CUSTOM_VALUE,
            'VARCHAR(255)',
            null,
            DescriptionResource::COLUMN_VIDEO_ATTRIBUTE,
            false,
            false
        );

        $modifier->commit();
    }

    private function changeComplianceDocumentConfig()
    {
        $query = $this->installer
            ->getConnection()
            ->select()
            ->from($this->getFullTableName(Tables::TABLE_EBAY_TEMPLATE_DESCRIPTION))
            ->where(
                sprintf(
                    '%s IS NOT NULL',
                    DescriptionResource::COLUMN_COMPLIANCE_DOCUMENTS
                )
            )
            ->query();

        while ($row = $query->fetch()) {
            $complianceDocumentsSetting = \Ess\M2ePro\Helper\Json::decode(
                $row[DescriptionResource::COLUMN_COMPLIANCE_DOCUMENTS]
            );

            if (empty($complianceDocumentsSetting)) {
                continue;
            }

            foreach ($complianceDocumentsSetting as &$document) {
                $document['document_mode'] = Description::COMPLIANCE_DOCUMENTS_MODE_ATTRIBUTE;
                $document['document_custom_value'] = '';
            }

            $complianceDocumentsSetting = \Ess\M2ePro\Helper\Json::encode($complianceDocumentsSetting);

            $this->getConnection()->update(
                $this->getFullTableName(Tables::TABLE_EBAY_TEMPLATE_DESCRIPTION),
                [
                    DescriptionResource::COLUMN_COMPLIANCE_DOCUMENTS => $complianceDocumentsSetting
                ],
                ['template_description_id = ?' => $row['template_description_id']]
            );
        }
    }
}

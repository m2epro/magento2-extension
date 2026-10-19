<?php

declare(strict_types=1);

namespace Ess\M2ePro\Setup\Update\y26_m10;

use Ess\M2ePro\Helper\Module\Database\Tables;

class RemoveOldHangingUploads extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    public function execute(): void
    {
        $this->deleteOldComplianceDocuments();
        $this->deleteOldComplianceDocumentsListingProduct();

        $this->deleteOldVideos();
    }

    private function deleteOldComplianceDocuments(): void
    {
        $borderDate = \Ess\M2ePro\Helper\Date::createCurrentGmt()->modify('-1 month');

        $this
            ->getConnection()
            ->delete(
                $this->getFullTableName(Tables::TABLE_EBAY_COMPLIANCE_DOCUMENTS),
                sprintf(
                    '%s = %s AND %s < "%s"',
                    \Ess\M2ePro\Model\ResourceModel\Ebay\ComplianceDocuments::COLUMN_STATUS,
                    \Ess\M2ePro\Model\Ebay\ComplianceDocuments::STATUS_UPLOADING,
                    \Ess\M2ePro\Model\ResourceModel\Ebay\ComplianceDocuments::COLUMN_UPDATE_DATE,
                    $borderDate->format('Y-m-d H:i:s')
                )
            );
    }

    private function deleteOldComplianceDocumentsListingProduct(): void
    {
        $select = $this
            ->getConnection()
            ->select()
            ->from(
                ['cdlp' => $this->getFullTableName(Tables::TABLE_EBAY_COMPLIANCE_DOCUMENTS_LISTING_PRODUCT)]
            )->joinLeft(
                ['cd' => $this->getFullTableName(Tables::TABLE_EBAY_COMPLIANCE_DOCUMENTS)],
                'cdlp.compliance_document_id = cd.id'
            )->where('cd.id IS NULL');

        $deleteSql = $this->getConnection()->deleteFromSelect($select, 'cdlp');

        $this->getConnection()
             ->query($deleteSql);
    }

    private function deleteOldVideos(): void
    {
        $borderDate = \Ess\M2ePro\Helper\Date::createCurrentGmt()->modify('-1 month');

        $this
            ->getConnection()
            ->delete(
                $this->getFullTableName(Tables::TABLE_EBAY_VIDEO),
                sprintf(
                    '%s = %s AND %s < "%s"',
                    \Ess\M2ePro\Model\ResourceModel\Ebay\Video::COLUMN_STATUS,
                    \Ess\M2ePro\Model\Ebay\Video::STATUS_UPLOADING,
                    \Ess\M2ePro\Model\ResourceModel\Ebay\Video::COLUMN_UPDATE_DATE,
                    $borderDate->format('Y-m-d H:i:s')
                )
            );
    }
}

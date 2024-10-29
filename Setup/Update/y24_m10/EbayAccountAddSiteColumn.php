<?php

declare(strict_types=1);

namespace Ess\M2ePro\Setup\Update\y24_m10;

use Ess\M2ePro\Helper\Module\Database\Tables;
use Ess\M2ePro\Model\ResourceModel\Ebay\Account as EbayAccountResource;

class EbayAccountAddSiteColumn extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    public function execute()
    {
        $this->addEbaySiteColumn();
        $this->fillEbaySiteColumn();
    }

    private function addEbaySiteColumn(): void
    {
        $modifier = $this->getTableModifier(Tables::TABLE_EBAY_ACCOUNT);

        $modifier->addColumn(
            EbayAccountResource::COLUMN_EBAY_SITE,
            'VARCHAR(20) NOT NULL',
            null,
            EbayAccountResource::COLUMN_FEEDBACKS_LAST_USED_ID,
            false,
            false
        );

        $modifier->commit();
    }

    private function fillEbaySiteColumn(): void
    {
        $tableName = $this->getFullTableName(Tables::TABLE_EBAY_ACCOUNT);
        $query = $this->installer
            ->getConnection()
            ->select()
            ->from($tableName)
            ->query();

        while ($row = $query->fetch()) {
            $accountId = $row[EbayAccountResource::COLUMN_ACCOUNT_ID];

            if (!isset($row[EbayAccountResource::COLUMN_INFO])) {
                continue;
            }

            $infoData = json_decode($row[EbayAccountResource::COLUMN_INFO], true);

            if (!is_array($infoData) || !isset($infoData['Site'])) {
                continue;
            }

            $this->installer->getConnection()->update(
                $tableName,
                [EbayAccountResource::COLUMN_EBAY_SITE => $infoData['Site']],
                [EbayAccountResource::COLUMN_ACCOUNT_ID => $accountId]
            );
        }
    }
}

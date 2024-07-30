<?php

declare(strict_types=1);

namespace Ess\M2ePro\Setup\Update\y24_m07;

use Ess\M2ePro\Model\ResourceModel\Amazon\Marketplace as ResourceAmazonMarketplace;
use Ess\M2ePro\Model\ResourceModel\Marketplace as ResourceMarketplace;

class EnableVatCalculationServiceForPolandAndSweden extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    public function execute(): void
    {
        foreach (['PL', 'SE'] as $countryCode) {
            $marketplaceId = $this->getConnection()
                                  ->select()
                                  ->from(
                                      $this->getFullTableName(
                                          \Ess\M2ePro\Helper\Module\Database\Tables::TABLE_MARKETPLACE
                                      )
                                  )
                                  ->where(ResourceMarketplace::COLUMN_CODE . ' = ?', $countryCode)
                                  ->where(ResourceMarketplace::COLUMN_COMPONENT_MODE . ' = ?', 'amazon')
                                  ->query()
                                  ->fetchColumn();

            $this->getConnection()->update(
                $this->getFullTableName(\Ess\M2ePro\Helper\Module\Database\Tables::TABLE_AMAZON_MARKETPLACE),
                [ResourceAmazonMarketplace::COLUMN_IS_VAT_CALCULATION_SERVICE_AVAILABLE => 1],
                [ResourceAmazonMarketplace::COLUMN_MARKETPLACE_ID . ' = ?' => $marketplaceId]
            );
        }
    }
}

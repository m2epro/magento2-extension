<?php

declare(strict_types=1);

namespace Ess\M2ePro\Setup\Update\y25_m06;

class EbayListingModifyAddProductModeAndAddAdditionalDataColumn extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    public function execute()
    {
        $this->clearColumnAddProductMode();
        $this->modifyColumnAddProductMode();
        $this->addAdditionalDataColumnForEbayListing();
    }

    private function clearColumnAddProductMode(): void
    {
        $this->getConnection()->update(
            $this->getFullTableName(\Ess\M2ePro\Helper\Module\Database\Tables::TABLE_EBAY_LISTING),
            [
                \Ess\M2ePro\Model\ResourceModel\Ebay\Listing::COLUMN_ADD_PRODUCT_MODE => null,
            ],
            sprintf(
                '%s IS NOT NULL',
                \Ess\M2ePro\Model\ResourceModel\Ebay\Listing::COLUMN_ADD_PRODUCT_MODE
            )
        );
    }

    private function modifyColumnAddProductMode(): void
    {
        $modifier = $this
            ->getTableModifier(\Ess\M2ePro\Helper\Module\Database\Tables::TABLE_EBAY_LISTING);

        $modifier->changeColumn(
            \Ess\M2ePro\Model\ResourceModel\Ebay\Listing::COLUMN_ADD_PRODUCT_MODE,
            'VARCHAR(20)',
            null,
            null,
            false
        );

        $modifier->commit();
    }

    private function addAdditionalDataColumnForEbayListing(): void
    {
        $this->getTableModifier(\Ess\M2ePro\Helper\Module\Database\Tables::TABLE_EBAY_LISTING)
             ->addColumn(
                 \Ess\M2ePro\Model\ResourceModel\Ebay\Listing::COLUMN_ADDITIONAL_DATA,
                 'LONGTEXT',
                 'NULL',
                 null,
                 false,
                 false
             )
             ->commit();
    }
}

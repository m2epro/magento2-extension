<?php

declare(strict_types=1);

namespace Ess\M2ePro\Setup\Update\y25_m05;

use Ess\M2ePro\Helper\Module\Database\Tables;

class DeleteEbayUnmanagedDuplicatesByListingProducts extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    public function execute()
    {
        $listing = $this->getFullTableName(Tables::TABLE_LISTING);
        $listingOther = $this->getFullTableName(Tables::TABLE_LISTING_OTHER);
        $ebayListingOther = $this->getFullTableName(Tables::TABLE_EBAY_LISTING_OTHER);
        $listingProduct = $this->getFullTableName(Tables::TABLE_LISTING_PRODUCT);
        $ebayListingProduct = $this->getFullTableName(Tables::TABLE_EBAY_LISTING_PRODUCT);
        $ebayItem = $this->getFullTableName(Tables::TABLE_EBAY_ITEM);

        $listingOthersSql = "SELECT lo.id FROM $ebayItem AS ei"
            . " JOIN $ebayListingProduct AS elp ON elp.ebay_item_id = ei.id"
            . " JOIN $listingProduct AS lp ON lp.id = elp.listing_product_id"
            . " JOIN $listing AS l ON l.id = lp.listing_id AND l.account_id = ei.account_id"
            . " JOIN $ebayListingOther AS elo ON elo.item_id = ei.item_id"
            . " JOIN $listingOther AS lo ON lo.id = elo.listing_other_id AND lo.account_id = ei.account_id";

        $retrievedIds = $this->getConnection()->fetchCol($listingOthersSql);
        foreach (array_chunk($retrievedIds, 100) as $chunk) {
            $this->getConnection()->delete(
                $ebayListingOther,
                ['listing_other_id IN (?)' => $chunk]
            );

            $this->getConnection()->delete(
                $listingOther,
                ['id IN (?)' => $chunk]
            );
        }
    }
}

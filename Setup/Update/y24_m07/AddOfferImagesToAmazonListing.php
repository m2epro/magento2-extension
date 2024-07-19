<?php

declare(strict_types=1);

namespace Ess\M2ePro\Setup\Update\y24_m07;

class AddOfferImagesToAmazonListing extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    public function execute(): void
    {
        $this->getTableModifier(\Ess\M2ePro\Helper\Module\Database\Tables::TABLE_AMAZON_LISTING)
             ->addColumn(
                 \Ess\M2ePro\Model\ResourceModel\Amazon\Listing::COLUMN_OFFER_IMAGES,
                 'LONGTEXT',
                 'NULL',
                 'condition_note_value',
                 false,
                 false
             )
            ->commit();
    }
}

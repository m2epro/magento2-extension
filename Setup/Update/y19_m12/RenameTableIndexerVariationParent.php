<?php

namespace Ess\M2ePro\Setup\Update\y19_m12;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

/**
 * Class \Ess\M2ePro\Setup\Update\y19_m12\RenameTableIndexerVariationParent
 */
class RenameTableIndexerVariationParent extends AbstractFeature
{
    //########################################

    public function execute()
    {
        $this->renameTable(
            'ebay_indexer_listing_product_variation_parent',
            'ebay_listing_product_indexer_variation_parent'
        );

        $this->renameTable(
            'amazon_indexer_listing_product_variation_parent',
            'amazon_listing_product_indexer_variation_parent'
        );

        $this->renameTable(
            'walmart_indexer_listing_product_variation_parent',
            'walmart_listing_product_indexer_variation_parent'
        );
    }

    //########################################
}

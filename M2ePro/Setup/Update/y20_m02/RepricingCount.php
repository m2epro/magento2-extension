<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */
// @codingStandardsIgnoreFile

namespace Ess\M2ePro\Setup\Update\y20_m02;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

/**
 * Class \Ess\M2ePro\Setup\Update\y20_m02\RepricingCount
 */
class RepricingCount extends AbstractFeature
{
    //########################################

    public function execute()
    {
        $listingProduct       = $this->getFullTableName('listing_product');
        $amazonListingProduct = $this->getFullTableName('amazon_listing_product');

        $this->getConnection()->exec(<<<SQL
UPDATE {$listingProduct} mlp
  JOIN {$amazonListingProduct} malp
    ON mlp.id = malp.listing_product_id
  SET mlp.additional_data = REPLACE(`additional_data`, 'repricing_disabled_count', 'repricing_not_managed_count')
  WHERE malp.is_repricing = 1;
SQL
        );

        $this->getConnection()->exec(<<<SQL
UPDATE {$listingProduct} mlp
  JOIN {$amazonListingProduct} malp
    ON mlp.id = malp.listing_product_id
  SET mlp.additional_data = REPLACE(`additional_data`, 'repricing_enabled_count', 'repricing_managed_count')
  WHERE malp.is_repricing = 1;
SQL
        );
    }

    //########################################
}

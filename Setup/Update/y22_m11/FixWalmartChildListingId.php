<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Update\y22_m11;

class FixWalmartChildListingId extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    /**
     * @return void
     */
    public function execute(): void
    {
        $connection = $this->installer->getConnection();
        $select = $connection->select();

        $select->join(
            ['wlp' => $this->getFullTableName('walmart_listing_product')],
            'lp.id = wlp.listing_product_id',
            null
        );
        $select->join(
            ['parent_lp' => $this->getFullTableName('listing_product')],
            'parent_lp.id = wlp.variation_parent_id',
            ['listing_id' => 'parent_lp.listing_id']
        );
        $select->where('lp.listing_id != parent_lp.listing_id');

        $updateQuery = $connection->updateFromSelect(
            $select,
            ['lp' => $this->getFullTableName('listing_product')]
        );

        $connection->query($updateQuery);
    }
}

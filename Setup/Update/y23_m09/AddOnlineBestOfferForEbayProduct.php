<?php

namespace Ess\M2ePro\Setup\Update\y23_m09;

use Magento\Framework\DB\Ddl\Table;

class AddOnlineBestOfferForEbayProduct extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    public function execute(): void
    {
        $modifier = $this->getTableModifier('ebay_listing_product');
        $modifier->addColumn(
            'online_best_offer',
            'VARCHAR(32)',
            null,
            'online_buyitnow_price',
            false,
            false
        );
        $modifier->commit();
    }
}

<?php
/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Update\y22_m05;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

/**
 * Class \Ess\M2ePro\Setup\Update\y22_m05\DropListingColumns
 */
class DropListingColumns extends AbstractFeature
{
    //########################################

    public function execute()
    {
        $this->getTableModifier('listing')
             ->dropColumn('products_total_count', true, false)
             ->dropColumn('products_active_count', true, false)
             ->dropColumn('products_inactive_count', true, false)
             ->dropColumn('items_active_count', true, false)
             ->commit();

        $this->getTableModifier('ebay_listing')
             ->dropColumn('products_sold_count', true, false)
             ->dropColumn('items_sold_count', true, false)
             ->commit();
    }

    //########################################
}

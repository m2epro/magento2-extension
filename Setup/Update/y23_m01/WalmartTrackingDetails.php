<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Update\y23_m01;

class WalmartTrackingDetails extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    /**
     * @return void
     * @throws \Ess\M2ePro\Model\Exception\Setup
     */
    public function execute()
    {
        $this->getTableModifier('walmart_order_item')
             ->addColumn(
                 'tracking_details',
                 "TEXT",
                 null,
                 'qty_purchased',
                 false,
                 false
             )
             ->commit();
    }
}

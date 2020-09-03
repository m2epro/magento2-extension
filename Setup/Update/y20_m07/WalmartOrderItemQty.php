<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Update\y20_m07;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

/**
 * Class \Ess\M2ePro\Setup\Update\y20_m07\WalmartOrderItemQty
 */
class WalmartOrderItemQty extends AbstractFeature
{
    //########################################

    /**
     * Magento describeTable works in wrong way when autoCommit = true
     * @throws \Ess\M2ePro\Model\Exception\Setup
     */
    public function execute()
    {
        $this->getTableModifier('walmart_order_item')
            ->changeAndRenameColumn('qty', 'qty_purchased', 'int(10) unsigned not null', '0', 'price', false)
            ->commit();
    }

    //########################################
}

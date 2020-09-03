<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Update\y20_m06;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

/**
 * Class \Ess\M2ePro\Setup\Update\y20_m06\RefundShippingCost
 */
class RefundShippingCost extends AbstractFeature
{
    //########################################

    public function execute()
    {
        $this->getTableModifier('amazon_order_item')
            ->addColumn('shipping_price', 'DECIMAL(12, 4) UNSIGNED NOT NULL', 0.0000, 'price');
    }

    //########################################
}
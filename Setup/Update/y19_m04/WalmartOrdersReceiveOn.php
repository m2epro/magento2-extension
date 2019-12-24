<?php
/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Update\y19_m04;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

/**
 * Class \Ess\M2ePro\Setup\Update\y19\WalmartOrdersReceiveOn_m04
 */
class WalmartOrdersReceiveOn extends AbstractFeature
{
    //########################################

    public function execute()
    {
        $this->getConfigModifier('synchronization')->getEntity('/walmart/orders/receive/', 'mode')->updateValue('1');
    }

    //########################################
}

<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Update\y22_m07;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

class FixFieldBuyerCancellationRequested extends AbstractFeature
{
    public function execute()
    {
        // Fix default value for field from default to zero
        $this->getTableModifier('walmart_order_item')
             ->changeColumn(
                 'buyer_cancellation_requested',
                 'SMALLINT UNSIGNED NOT NULL',
                 0
             );
    }
}

<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Update\y23_m04;

class EbayBuyerInitiatedOrderCancellation extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    /**
     * @return void
     * @throws \Ess\M2ePro\Model\Exception\Setup
     */
    public function execute()
    {
        $modifier = $this->getTableModifier('ebay_order');
        $modifier->addColumn(
            'buyer_cancellation_status',
            'SMALLINT UNSIGNED NOT NULL',
            0,
            'cancellation_status'
        );
    }
}

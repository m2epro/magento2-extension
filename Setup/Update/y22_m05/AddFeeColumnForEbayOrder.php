<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Update\y22_m05;

class AddFeeColumnForEbayOrder extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    public function execute()
    {
        $this->getTableModifier('ebay_order')
             ->addColumn(
                 'final_fee',
                 'decimal(10,2) unsigned DEFAULT NULL',
                 'NULL',
                 'saved_amount'
             );
    }
}

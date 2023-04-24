<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Update\y22_m05;

class AddFeeColumnForEbayOrder extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    /**
     * @return void
     * @throws \Ess\M2ePro\Model\Exception\Setup
     */
    public function execute()
    {
        $tableModifier = $this->getTableModifier('ebay_order');
        if (!$tableModifier->isColumnExists('final_fee')) {
            $tableModifier
                 ->addColumn(
                     'final_fee',
                     'decimal(10,2) unsigned DEFAULT NULL',
                     'NULL',
                     'saved_amount'
                 );
        }
    }
}

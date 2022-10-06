<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Update\y22_m10;

use Magento\Framework\DB\Ddl\Table as DdlTable;

class AddIsSoldByAmazonColumnToAmazonOrder extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    /**
     * @return void
     * @throws \Ess\M2ePro\Model\Exception\Setup
     */
    public function execute()
    {
        $this->getTableModifier('amazon_order')
             ->addColumn(
                 'is_sold_by_amazon',
                 "SMALLINT UNSIGNED NOT NULL",
                 0,
                 'is_prime',
                 false,
                 false
             )
            ->commit();
    }
}

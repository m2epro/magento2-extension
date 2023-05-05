<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Update\y23_m04;

class ChangeTypeProductAddIds extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    public function execute(): void
    {
        $this->getTableModifier('ebay_listing')
             ->changeColumn('product_add_ids', 'LONGTEXT', 'NULL', null, false)
             ->commit();

        $this->getTableModifier('amazon_listing')
             ->changeColumn('product_add_ids', 'LONGTEXT', 'NULL', null, false)
             ->commit();
    }
}

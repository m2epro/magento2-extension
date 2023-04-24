<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Update\y23_m04;

class SetIsVatEbayMarketplacePL extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    public function execute(): void
    {
        $ebayMarketplaceTable = $this->getFullTableName('ebay_marketplace');
        $this->installer->getConnection()->update(
            $ebayMarketplaceTable,
            ['is_vat' => 1],
            'marketplace_id = 21'
        );
    }
}

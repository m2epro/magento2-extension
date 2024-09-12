<?php

declare(strict_types=1);

namespace Ess\M2ePro\Setup\Update\y24_m09;

use Ess\M2ePro\Helper\Module\Database\Tables as TablesHelper;

class RemoveIsNewAsinAvailableFromAmazonMarketplace extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    public function execute(): void
    {
        $this->getTableModifier(TablesHelper::TABLE_AMAZON_MARKETPLACE)
             ->dropColumn('is_new_asin_available');
    }
}

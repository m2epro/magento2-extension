<?php

namespace Ess\M2ePro\Setup\Update\y20_m11;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

/**
 * Class Ess\M2ePro\Setup\Update\y20_m11\AmazonDuplicatedMarketplaceFeature
 */
class AmazonDuplicatedMarketplaceFeature extends AbstractFeature
{
    //########################################

    public function execute()
    {
        $this->getTableModifier('amazon_marketplace')
            ->dropColumn('is_upload_invoices_available', true, false)
            ->commit();
    }

    //########################################
}

<?php

namespace Ess\M2ePro\Setup\Update\y19_m12;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

/**
 * Class \Ess\M2ePro\Setup\Update\y19_m12\RemoveEbayTranslation
 */
class RemoveEbayTranslation extends AbstractFeature
{
    //########################################

    public function execute()
    {
        $this->getTableModifier('ebay_listing_product')
            ->dropColumn('translation_status', true, false)
            ->dropColumn('translation_service', true, false)
            ->dropColumn('translated_date', true, false)
            ->commit();

        $this->getTableModifier('ebay_marketplace')
            ->dropColumn('translation_service_mode', true, false)
            ->commit();

        $this->getTableModifier('ebay_account')
            ->dropColumn('translation_hash', true, false)
            ->dropColumn('translation_info', true, false)
            ->commit();

        $this->getConfigModifier('module')->delete('/ebay/translation_services/gold/');
        $this->getConfigModifier('module')->delete('/ebay/translation_services/silver/');
        $this->getConfigModifier('module')->delete('/ebay/translation_services/platinum/');
    }

    //########################################
}

<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Update\y20_m05;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

/**
 * Class \Ess\M2ePro\Setup\Update\y20_m05\RemovePriceDeviationRules
 */
class RemovePriceDeviationRules extends AbstractFeature
{
    //########################################

    public function execute()
    {
        foreach (['ebay', 'amazon', 'walmart'] as $component) {
            $this->getTableModifier("{$component}_template_synchronization")
                ->dropColumn('revise_update_price_max_allowed_deviation_mode', true, false)
                ->dropColumn('revise_update_price_max_allowed_deviation', true, false)
                ->commit();
        }

        $this->getConnection()
            ->delete(
                $this->getFullTableName('listing_product_instruction'),
                ['type = ?' => 'template_synchronization_revise_price_settings_changed']
            );
    }

    //########################################
}

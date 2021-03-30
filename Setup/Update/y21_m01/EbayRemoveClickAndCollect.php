<?php

namespace Ess\M2ePro\Setup\Update\y21_m01;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

/**
 * Class \Ess\M2ePro\Setup\Update\y21_m01\EbayRemoveClickAndCollect
 */
class EbayRemoveClickAndCollect extends AbstractFeature
{
    //########################################

    public function execute()
    {
        $this->getTableModifier('ebay_marketplace')
            ->dropColumn('is_click_and_collect', true, false)
            ->commit();

        $this->getTableModifier('ebay_template_shipping')
            ->dropColumn('click_and_collect_mode', false, false)
            ->commit();

        $this->getConnection()->update(
            $this->getFullTableName('ebay_template_shipping_calculated'),
            [
                'package_size_mode'  => 0,
                'package_size_value' => ''
            ],
            ['package_size_value = ?' => 'None']
        );
    }

    //########################################
}

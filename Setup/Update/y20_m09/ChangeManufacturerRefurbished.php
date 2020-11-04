<?php

namespace Ess\M2ePro\Setup\Update\y20_m09;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

/**
 * Class \Ess\M2ePro\Setup\Update\y20_m09\ChangeManufacturerRefurbished
 */
class ChangeManufacturerRefurbished extends AbstractFeature
{
    //########################################

    public function execute()
    {
        $tableModifier = $this->getTableModifier('ebay_template_description');
        if (!$tableModifier->isColumnExists('condition_mode') ||
            !$tableModifier->isColumnExists('condition_value')) {
            return;
        }

        $this->getConnection()->update(
            $this->getFullTableName('ebay_template_description'),
            // Ess\M2ePro\Model\Ebay\Template\Description::CONDITION_EBAY_SELLER_REFURBISHED
            ['condition_value' => 2500],
            [
                'condition_value = ?' => 2000,
                'condition_mode = ?'  => 0 // Ess\M2ePro\Model\Ebay\Template\Description::CONDITION_MODE_EBAY
            ]
        );
    }

    //########################################
}

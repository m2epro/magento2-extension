<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Update\y20_m08;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

/**
 * Class  \Ess\M2ePro\Setup\Update\y20_m08\EbayManagedPayments
 */
class EbayManagedPayments extends AbstractFeature
{
    //########################################

    public function execute()
    {
        $this->getTableModifier('ebay_template_payment')->addColumn(
            'managed_payments_mode',
            'TINYINT(2) UNSIGNED NOT NULL',
            '0',
            'is_custom_template'
        );

        $tableModifier = $this->getTableModifier('ebay_marketplace');

        if ($tableModifier->isColumnExists('is_managed_payments')) {
            return;
        }

        $tableModifier->addColumn(
            'is_managed_payments',
            'TINYINT(2) UNSIGNED NOT NULL',
            '0',
            'is_metric_measurement_system',
            true
        );

        $this->getConnection()->update(
            $this->getFullTableName('ebay_marketplace'),
            ['is_managed_payments' => 1],
            ['marketplace_id IN (?)' => [1, 2, 3, 4, 8]]
        );
    }

    //########################################
}

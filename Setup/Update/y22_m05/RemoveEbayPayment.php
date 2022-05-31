<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Update\y22_m05;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

/**
 * Class \Ess\M2ePro\Setup\Update\y22_m05\RemoveEbayPayment
 */
class RemoveEbayPayment extends AbstractFeature
{
    public function execute()
    {
        $this->removeColumns();
        $this->removeTables();
    }

    private function removeTables()
    {
        $this->getConnection()->dropTable($this->getFullTableName('ebay_template_payment'));
        $this->getConnection()->dropTable($this->getFullTableName('ebay_template_payment_service'));
    }

    private function removeColumns()
    {
        $this->getTableModifier('ebay_listing')
            ->dropColumn('template_payment_id', true, false)
            ->commit();

        $this->getTableModifier('ebay_listing_product')
            ->dropColumn('online_payment_data');

        $this->getTableModifier('ebay_listing_product')
            ->dropColumn('template_payment_mode', true, false)
            ->dropColumn('template_payment_id', true, false)
            ->commit();

        $this->getTableModifier('ebay_template_synchronization')
            ->dropColumn('revise_update_payment');
    }
}

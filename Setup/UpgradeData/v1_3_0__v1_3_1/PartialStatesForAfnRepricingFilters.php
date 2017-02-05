<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2016 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\UpgradeData\v1_3_0__v1_3_1;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

class PartialStatesForAfnRepricingFilters extends AbstractFeature
{
    //########################################

    public function getBackupTables()
    {
        return ['amazon_listing_product'];
    }

    public function execute()
    {
        $this->getTableModifier('amazon_listing_product')
             ->addColumn('variation_parent_afn_state', 'SMALLINT(5) UNSIGNED',
                         'NULL', 'is_general_id_owner', true, false)
             ->addColumn('variation_parent_repricing_state', 'SMALLINT(5) UNSIGNED',
                         'NULL', 'variation_parent_afn_state', true, false)
             ->commit();
    }

    //########################################
}
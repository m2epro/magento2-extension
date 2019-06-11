<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Upgrade\v1_1_0__v1_2_0;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

class PartialReviseByShippingServices extends AbstractFeature
{
    //########################################

    public function getBackupTables()
    {
        return ['ebay_template_synchronization'];
    }

    public function execute()
    {
        $this->getTableModifier('ebay_template_synchronization')
             ->addColumn(
                 'revise_update_shipping_services','SMALLINT(4) UNSIGNED NOT NULL',NULL,'revise_update_specifics'
             );
    }

    //########################################
}
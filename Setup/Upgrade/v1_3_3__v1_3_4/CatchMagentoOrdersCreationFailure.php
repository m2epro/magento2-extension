<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Upgrade\v1_3_3__v1_3_4;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

class CatchMagentoOrdersCreationFailure extends AbstractFeature
{
    //########################################

    public function getBackupTables()
    {
        return ['order'];
    }

    public function execute()
    {
        $this->getTableModifier('order')
             ->addColumn(
                 'magento_order_creation_failure', 'SMALLINT(5) UNSIGNED NOT NULL', '0',
                 'magento_order_id', true, false
             )
            ->addColumn(
                'magento_order_creation_fails_count', 'SMALLINT(5) UNSIGNED NOT NULL', '0',
                'magento_order_creation_failure', true, false
            )
             ->addColumn(
                 'magento_order_creation_latest_attempt_date', 'DATETIME', NULL,
                 'magento_order_creation_fails_count', true, false
             )
             ->commit();
    }

    //########################################
}
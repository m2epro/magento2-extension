<?php
/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Update\y19_m04;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

/**
 * Class \Ess\M2ePro\Setup\Update\y19\WalmartAuthenticationForCA_m04
 */
class WalmartAuthenticationForCA extends AbstractFeature
{
    //########################################

    public function execute()
    {
        $this->getTableModifier('walmart_account')
            ->addColumn('old_private_key', 'TEXT', 'NULL', 'consumer_id', false, false)
            ->commit();
    }

    //########################################
}

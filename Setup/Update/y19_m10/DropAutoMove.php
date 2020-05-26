<?php
/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Update\y19_m10;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

/**
 * Class \Ess\M2ePro\Setup\Update\y19_m10\DropAutoMove
 */
class DropAutoMove extends AbstractFeature
{
    //########################################

    public function execute()
    {
        $this->getTableModifier('amazon_account')
            ->dropColumn('other_listings_move_mode', true, false)
            ->dropColumn('other_listings_move_settings', true, false)
            ->commit();
    }

    //########################################
}

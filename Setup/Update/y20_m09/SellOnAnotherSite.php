<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Update\y20_m09;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

/**
 * Class \Ess\M2ePro\Setup\Update\y20_m09\SellOnAnotherSite
 */
class SellOnAnotherSite extends AbstractFeature
{
    //########################################

    public function execute()
    {
        $this->getConfigModifier('module')->delete(
            '/ebay/configuration/',
            'sell_on_another_marketplace_tutorial_shown'
        );
    }

    //########################################
}

<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Update\y20_m06;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

/**
 * Class \Ess\M2ePro\Setup\Update\y20_m06\AmazonConfig
 */
class AmazonConfig extends AbstractFeature
{
    //########################################

    public function execute()
    {
        $this->getConfigModifier('module')->getEntity('/amazon/business/', 'mode')
            ->updateGroup('/amazon/configuration/')
            ->updateKey('business_mode');
    }

    //########################################
}

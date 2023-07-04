<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Update\y23_m06;

class IgnoreVariationMpnInResolverConfig extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    /**
     * @return void
     */
    public function execute(): void
    {
        $config = $this->getConfigModifier('module');
        $config->insert(
            '/ebay/configuration/',
            'ignore_variation_mpn_in_resolver',
            '0'
        );
    }
}

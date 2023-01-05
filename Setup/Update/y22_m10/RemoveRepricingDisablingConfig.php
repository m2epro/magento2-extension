<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Update\y22_m10;

class RemoveRepricingDisablingConfig extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    /**
     * @return void
     */
    public function execute()
    {
        $this->getConfigModifier('module')->delete('/amazon/repricing/', 'mode');
    }
}

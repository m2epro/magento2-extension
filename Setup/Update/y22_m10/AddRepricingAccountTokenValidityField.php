<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Update\y22_m10;

class AddRepricingAccountTokenValidityField extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    /**
     * @return void
     * @throws \Ess\M2ePro\Model\Exception\Setup
     */
    public function execute()
    {
        $this->getTableModifier('amazon_account_repricing')
             ->addColumn(
                 'invalid',
                 "SMALLINT UNSIGNED NOT NULL",
                 0,
                 'token',
                 false,
                 false
             )
            ->commit();
    }
}

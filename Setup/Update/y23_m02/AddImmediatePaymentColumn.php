<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Update\y23_m02;

class AddImmediatePaymentColumn extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    /**
     * @return void
     * @throws \Ess\M2ePro\Model\Exception\Setup
     */
    public function execute()
    {
        $this->getTableModifier('ebay_template_selling_format')
             ->addColumn(
                 'paypal_immediate_payment',
                 'SMALLINT UNSIGNED NOT NULL',
                 0,
                 'ignore_variations',
                 false,
                 false
             )
             ->commit();
    }
}

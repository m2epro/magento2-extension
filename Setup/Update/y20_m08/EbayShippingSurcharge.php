<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Update\y20_m08;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

/**
 * Class  \Ess\M2ePro\Setup\Update\y20_m08\EbayShippingSurcharge
 */
class EbayShippingSurcharge extends AbstractFeature
{
    //########################################

    public function execute()
    {
        $this->getTableModifier('ebay_template_shipping_service')->dropColumn('cost_surcharge_value');
    }

    //########################################
}

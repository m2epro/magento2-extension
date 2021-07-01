<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Update\y21_m06;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

/**
 * Class \Ess\M2ePro\Setup\Update\y21_m06\EbayTaxReference
 */

class EbayTaxReference extends AbstractFeature
{
    //########################################

    public function execute()
    {
        $this->getTableModifier('ebay_order')
            ->addColumn('tax_reference', 'VARCHAR(72) DEFAULT NULL', null, 'tax_details');

    }

    //########################################
}

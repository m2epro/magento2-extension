<?php

namespace Ess\M2ePro\Setup\Update\y21_m07;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

/**
 * Class \Ess\M2ePro\Setup\Update\y21_m07\AmazonIossNumber
 */
class AmazonIossNumber extends AbstractFeature
{
    //########################################

    public function execute()
    {
        $this->getTableModifier('amazon_order')
            ->addColumn('ioss_number', 'VARCHAR(72) DEFAULT NULL', null, 'tax_details');
    }

    //########################################
}

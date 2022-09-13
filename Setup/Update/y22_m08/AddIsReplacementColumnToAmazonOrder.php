<?php

namespace Ess\M2ePro\Setup\Update\y22_m08;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

class AddIsReplacementColumnToAmazonOrder extends AbstractFeature
{
    public function execute()
    {
        $this->getTableModifier('amazon_order')
             ->addColumn('is_replacement', 'SMALLINT UNSIGNED NOT NULL', 0, 'is_business');
    }
}

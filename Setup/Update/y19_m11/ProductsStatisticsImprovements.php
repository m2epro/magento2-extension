<?php

namespace Ess\M2ePro\Setup\Update\y19_m11;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

/**
 * Class \Ess\M2ePro\Setup\Update\y19_m11\ProductsStatisticsImprovements
 */
class ProductsStatisticsImprovements extends AbstractFeature
{
    //########################################

    public function execute()
    {
        $this->getConfigModifier('cache')->delete('/servicing/statistic/', 'last_run');
    }

    //########################################
}

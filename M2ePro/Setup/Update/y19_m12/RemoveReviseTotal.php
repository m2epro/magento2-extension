<?php

namespace Ess\M2ePro\Setup\Update\y19_m12;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

/**
 * Class \Ess\M2ePro\Setup\Update\y19_m12\RemoveReviseTotal
 */
class RemoveReviseTotal extends AbstractFeature
{
    //########################################

    public function execute()
    {
        $this->getConfigModifier('module')->delete('/view/synchronization/revise_total/');
        $this->getConfigModifier('module')->delete('/listing/product/revise/total/ebay/');
        $this->getConfigModifier('module')->delete('/listing/product/revise/total/amazon/');
        $this->getConfigModifier('module')->delete('/listing/product/revise/total/walmart/');
    }

    //########################################
}

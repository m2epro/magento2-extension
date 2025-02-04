<?php

namespace Ess\M2ePro\Model\Listing\Product\Variation;

abstract class Updater extends \Ess\M2ePro\Model\AbstractModel
{
    abstract public function process(\Ess\M2ePro\Model\Listing\Product $listingProduct);

    public function afterMassProcessEvent()
    {
        return null;
    }
}

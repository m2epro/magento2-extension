<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Observer\Product;

class Delete extends AbstractProduct
{
    //########################################

    public function process()
    {
        if ($this->getProductId() <= 0) {
            return;
        }

        $this->activeRecordFactory->getObject('Listing')->removeDeletedProduct($this->getProduct());
        $this->activeRecordFactory->getObject('Listing\Other')->unmapDeletedProduct($this->getProduct());
        $this->modelFactory->getObject('Item')->removeDeletedProduct($this->getProduct());
        $this->activeRecordFactory->getObject('ProductChange')->removeDeletedProduct($this->getProduct());
    }

    //########################################
}
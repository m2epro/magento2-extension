<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Observer\Product\AddUpdate;

/**
 * Class \Ess\M2ePro\Observer\Product\AddUpdate\AbstractAddUpdate
 */
abstract class AbstractAddUpdate extends \Ess\M2ePro\Observer\Product\AbstractProduct
{
    private $affectedListingsProducts = [];

    //########################################

    /**
     * @return bool
     */
    public function canProcess()
    {
        return (string)$this->getEvent()->getProduct()->getSku() != '';
    }

    //########################################

    abstract protected function isAddingProductProcess();

    //########################################

    protected function areThereAffectedItems()
    {
        return !empty($this->getAffectedListingsProducts());
    }

    // ---------------------------------------

    protected function getAffectedListingsProducts()
    {
        if (!empty($this->affectedListingsProducts)) {
            return $this->affectedListingsProducts;
        }

        return $this->affectedListingsProducts = $this->activeRecordFactory
                                                      ->getObject('Listing\Product')
                                                      ->getResource()
                                                      ->getItemsByProductId($this->getProductId());
    }

    //########################################
}

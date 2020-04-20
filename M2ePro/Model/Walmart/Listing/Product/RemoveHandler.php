<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */


namespace Ess\M2ePro\Model\Walmart\Listing\Product;

use Ess\M2ePro\Model\Walmart\Listing\Product\Variation\Manager\Type\Relation\ParentRelation;

/**
 * Class \Ess\M2ePro\Model\Walmart\Listing\Product\RemoveHandler
 */
class RemoveHandler extends \Ess\M2ePro\Model\Listing\Product\RemoveHandler
{
    /** @var \Ess\M2ePro\Model\Walmart\Listing\Product */
    protected $parentWalmartListingProductForProcess = null;

    //########################################

    protected function eventBeforeProcess()
    {
        parent::eventBeforeProcess();

        $variationManager = $this->getWalmartListingProduct()->getVariationManager();

        if ($variationManager->isRelationChildType()) {

            /** @var \Ess\M2ePro\Model\Walmart\Listing\Product $parentWalmartListingProduct */
            $parentWalmartListingProduct = $variationManager
                ->getTypeModel()
                ->getWalmartParentListingProduct();

            $this->parentWalmartListingProductForProcess = $parentWalmartListingProduct;

            /** @var \Ess\M2ePro\Model\Walmart\Listing\Product\Variation\Manager\Type\Relation\Child $childTypeModel */
            $childTypeModel = $variationManager->getTypeModel();

            if ($childTypeModel->isVariationProductMatched()) {
                $parentWalmartListingProduct->getVariationManager()->getTypeModel()->addRemovedProductOptions(
                    $variationManager->getTypeModel()->getProductOptions()
                );
            }
        }
    }

    protected function eventAfterProcess()
    {
        parent::eventAfterProcess();

        if ($this->parentWalmartListingProductForProcess === null) {
            return;
        }

        /** @var ParentRelation $parentTypeModel */
        $parentTypeModel = $this->parentWalmartListingProductForProcess->getVariationManager()->getTypeModel();
        $parentTypeModel->getProcessor()->process();
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Walmart\Listing\Product
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function getWalmartListingProduct()
    {
        return $this->listingProduct->getChildObject();
    }

    //########################################
}

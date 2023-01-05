<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */


namespace Ess\M2ePro\Model\Walmart\Listing\Product;

use Ess\M2ePro\Model\Walmart\Listing\Product\Variation\Manager\Type\Relation\ParentRelation;

class RemoveHandler extends \Ess\M2ePro\Model\Listing\Product\RemoveHandler
{
    /** @var \Ess\M2ePro\Model\Walmart\Listing\Product */
    private $parentWalmartListingProductForProcess;

    protected function eventBeforeProcess(): void
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

    protected function eventAfterProcess(): void
    {
        parent::eventAfterProcess();

        if ($this->parentWalmartListingProductForProcess === null) {
            return;
        }

        /** @var ParentRelation $parentTypeModel */
        $parentTypeModel = $this->parentWalmartListingProductForProcess->getVariationManager()->getTypeModel();
        $parentTypeModel->getProcessor()->process();
    }

    // ----------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Walmart\Listing\Product
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    private function getWalmartListingProduct()
    {
        return $this->getListingProduct()->getChildObject();
    }
}

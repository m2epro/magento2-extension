<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Listing\Product;

use \Ess\M2ePro\Model\Amazon\Listing\Product\Variation\Manager\Type\Relation\ChildRelation;
use \Ess\M2ePro\Model\Amazon\Listing\Product\Variation\Manager\Type\Relation\ParentRelation;

/**
 * Class \Ess\M2ePro\Model\Amazon\Listing\Product\RemoveHandler
 */
class RemoveHandler extends \Ess\M2ePro\Model\Listing\Product\RemoveHandler
{
    /** @var \Ess\M2ePro\Model\Amazon\Listing\Product */
    protected $parentAmazonListingProductForProcess = null;

    //########################################

    protected function eventBeforeProcess()
    {
        parent::eventBeforeProcess();

        $variationManager = $this->getAmazonListingProduct()->getVariationManager();

        if ($variationManager->isRelationChildType()) {

            /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $parentAmazonListingProduct */
            $parentAmazonListingProduct = $variationManager
                ->getTypeModel()
                ->getAmazonParentListingProduct();

            $this->parentAmazonListingProductForProcess = $parentAmazonListingProduct;

            /** @var ChildRelation $childTypeModel */
            $childTypeModel = $variationManager->getTypeModel();

            if ($childTypeModel->isVariationProductMatched()) {
                $parentAmazonListingProduct->getVariationManager()->getTypeModel()->addRemovedProductOptions(
                    $variationManager->getTypeModel()->getProductOptions()
                );
            }
        }
    }

    protected function eventAfterProcess()
    {
        parent::eventAfterProcess();

        if ($this->parentAmazonListingProductForProcess === null) {
            return;
        }

        /** @var ParentRelation $parentTypeModel */
        $parentTypeModel = $this->parentAmazonListingProductForProcess->getVariationManager()->getTypeModel();
        try {
            $parentTypeModel->getProcessor()->process();
        } catch (\Exception $exception) {
            $this->getHelper('Module\Exception')->process($exception, false);
        }
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Amazon\Listing\Product
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function getAmazonListingProduct()
    {
        return $this->listingProduct->getChildObject();
    }

    //########################################
}

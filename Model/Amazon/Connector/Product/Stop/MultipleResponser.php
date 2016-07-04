<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Connector\Product\Stop;

class MultipleResponser extends \Ess\M2ePro\Model\Amazon\Connector\Product\Responser
{
    /** @var \Ess\M2ePro\Model\Listing\Product[] $parentsForProcessing */
    protected $parentsForProcessing = array();

    // ########################################

    protected function getSuccessfulMessage(\Ess\M2ePro\Model\Listing\Product $listingProduct)
    {
        // M2ePro\TRANSLATIONS
        // Item was successfully Stopped
        return 'Item was successfully Stopped';
    }

    // ########################################

    public function eventAfterExecuting()
    {
        if (!empty($this->params['params']['remove'])) {
            foreach ($this->listingsProducts as $listingProduct) {
                /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
                $amazonListingProduct = $listingProduct->getChildObject();

                $variationManager = $amazonListingProduct->getVariationManager();

                if ($variationManager->isRelationChildType()) {
                    $childTypeModel = $variationManager->getTypeModel();

                    $parentListingProduct = $childTypeModel->getParentListingProduct();
                    $this->parentsForProcessing[$parentListingProduct->getId()] = $parentListingProduct;

                    if ($childTypeModel->isVariationProductMatched()) {
                        $parentAmazonListingProduct = $childTypeModel->getAmazonParentListingProduct();

                        $parentAmazonListingProduct->getVariationManager()->getTypeModel()->addRemovedProductOptions(
                            $childTypeModel->getProductOptions()
                        );
                    }
                }

                $listingProduct->setData('status', \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED);
                $listingProduct->save();
                $listingProduct->delete();
            }
        }

        parent::eventAfterExecuting();
    }

    protected function inspectProducts()
    {
        if (empty($this->params['params']['remove'])) {
            parent::inspectProducts();
        }
    }

    protected function processParentProcessors()
    {
        if (empty($this->params['params']['remove'])) {
            parent::processParentProcessors();
            return;
        }

        foreach ($this->parentsForProcessing as $listingProduct) {
            /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
            $amazonListingProduct = $listingProduct->getChildObject();
            $amazonListingProduct->getVariationManager()->getTypeModel()->getProcessor()->process();
        }
    }

    // ########################################
}
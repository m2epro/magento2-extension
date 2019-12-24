<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Connector\Product\Delete;

/**
 * Class \Ess\M2ePro\Model\Amazon\Connector\Product\Delete\Responser
 */
class Responser extends \Ess\M2ePro\Model\Amazon\Connector\Product\Responser
{
    /** @var \Ess\M2ePro\Model\Listing\Product $parentForProcessing */
    protected $parentForProcessing = null;

    // ########################################

    protected function getSuccessfulMessage()
    {
        // M2ePro\TRANSLATIONS
        // Item was successfully Deleted
        return 'Item was successfully Deleted';
    }

    // ########################################

    public function eventAfterExecuting()
    {
        $responseData = $this->getPreparedResponseData();

        if (!empty($this->params['params']['remove']) && !empty($responseData['request_time'])) {

            /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
            $amazonListingProduct = $this->listingProduct->getChildObject();

            $variationManager = $amazonListingProduct->getVariationManager();

            if ($variationManager->isRelationChildType()) {
                $childTypeModel = $variationManager->getTypeModel();

                $parentListingProduct = $childTypeModel->getParentListingProduct();
                $this->parentForProcessing = $parentListingProduct;

                if ($childTypeModel->isVariationProductMatched()) {
                    /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $parentAmazonListingProduct */
                    $parentAmazonListingProduct = $childTypeModel->getAmazonParentListingProduct();

                    $parentAmazonListingProduct->getVariationManager()->getTypeModel()->addRemovedProductOptions(
                        $childTypeModel->getProductOptions()
                    );
                }
            }

            $this->listingProduct->setData('status', \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED);
            $this->listingProduct->save();
            $this->listingProduct->delete();
        }

        parent::eventAfterExecuting();
    }

    protected function inspectProduct()
    {
        if (empty($this->params['params']['remove'])) {
            parent::inspectProduct();
            return;
        }

        $responseData = $this->getPreparedResponseData();
        if (!empty($responseData['request_time'])) {
            return;
        }

        $configurator = $this->getConfigurator();
        if (!empty($responseData['start_processing_date'])) {
            $configurator->setParams(['start_processing_date' => $responseData['start_processing_date']]);
        }

        $dispatcherObject = $this->modelFactory->getObject('Amazon_Connector_Product_Dispatcher');
        $dispatcherObject->process(
            \Ess\M2ePro\Model\Listing\Product::ACTION_DELETE,
            [$this->listingProduct->getId()],
            $this->params['params']
        );
    }

    protected function processParentProcessor()
    {
        if (empty($this->params['params']['remove'])) {
            parent::processParentProcessor();
            return;
        }

        if ($this->parentForProcessing === null) {
            return;
        }

        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
        $amazonListingProduct = $this->listingProduct->getChildObject();
        $amazonListingProduct->getVariationManager()->getTypeModel()->getProcessor()->process();
    }

    // ########################################
}

<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Connector\Product\ListAction;

/**
 * @method \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\ListAction\Response getResponseObject()
 */
class Responser extends \Ess\M2ePro\Model\Amazon\Connector\Product\Responser
{
    // ########################################

    protected function getSuccessfulMessage()
    {
        // M2ePro\TRANSLATIONS
        // Item was successfully Listed
        return 'Item was successfully Listed';
    }

    // ########################################

    protected function inspectProduct()
    {
        parent::inspectProduct();

        $runner = $this->modelFactory->getObject('Synchronization\Templates\Synchronization\Runner');
        $runner->setConnectorModel('Amazon\Connector\Product\Dispatcher');
        $runner->setMaxProductsPerStep(100);

        if (!$this->isSuccess) {
            if ($this->listingProduct->needSynchRulesCheck()) {
                $configurator = $this->modelFactory->getObject('Amazon\Listing\Product\Action\Configurator');

                $responseData = $this->getPreparedResponseData();
                if (empty($responseData['request_time']) && !empty($responseData['start_processing_date'])) {
                    $configurator->setParams(array('start_processing_date' => $responseData['start_processing_date']));
                }

                $runner->addProduct(
                    $this->listingProduct, \Ess\M2ePro\Model\Listing\Product::ACTION_LIST, $configurator
                );
                $runner->execute();
            }

            return;
        }

        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
        $amazonListingProduct = $this->listingProduct->getChildObject();

        if (!$amazonListingProduct->getVariationManager()->isRelationParentType()) {
            return;
        }

        $childListingProducts = $amazonListingProduct->getVariationManager()->getTypeModel()
                                                     ->getChildListingsProducts();

        if (empty($childListingProducts)) {
            return;
        }

        $inspector = $this->modelFactory->getObject('Amazon\Synchronization\Templates\Synchronization\Inspector');

        foreach ($childListingProducts as $listingProduct) {
            /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */

            if (!$inspector->isMeetListRequirements($listingProduct)) {
                continue;
            }

            /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
            $amazonListingProduct = $listingProduct->getChildObject();
            $amazonTemplate = $amazonListingProduct->getAmazonSynchronizationTemplate();

            if ($amazonTemplate->isListAdvancedRulesEnabled() &&
                !$inspector->isMeetAdvancedListRequirements($this->listingProduct)) {

                continue;
            }

            $configurator = $this->modelFactory->getObject('Amazon\Listing\Product\Action\Configurator');

            $runner->addProduct(
                $listingProduct, \Ess\M2ePro\Model\Listing\Product::ACTION_LIST, $configurator
            );
        }

        $runner->execute();
    }

    // ########################################

    protected function processSuccess(array $params = array())
    {
        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
        $amazonListingProduct = $this->listingProduct->getChildObject();

        if ($amazonListingProduct->getVariationManager()->isRelationMode() &&
            !$this->getRequestDataObject()->hasProductId() &&
            empty($params['general_id'])
        ) {
            $message = $this->modelFactory->getObject('Connector\Connection\Response\Message');
            $message->initFromPreparedData(
                'Unexpected error. The ASIN/ISBN for Parent or Child Product was not returned from Amazon.
                 Operation cannot be finished correctly.',
               \Ess\M2ePro\Model\Connector\Connection\Response\Message::TYPE_ERROR
            );

            $this->getLogger()->logListingProductMessage(
                $this->listingProduct,
                $message,
                \Ess\M2ePro\Model\Log\AbstractModel::PRIORITY_MEDIUM
            );

            return;
        }

        parent::processSuccess($params);
    }

    protected function getSuccessfulParams()
    {
        $responseData = $this->getPreparedResponseData();

        if (empty($responseData['asins'])) {
            return array();
        }

        return array('general_id' => $responseData['asins']);
    }

    // ########################################
}
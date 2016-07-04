<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

/**
 * @method \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\ListAction\Response getResponseObject($listingProduct)
 */

namespace Ess\M2ePro\Model\Amazon\Connector\Product\ListAction;

class MultipleResponser extends \Ess\M2ePro\Model\Amazon\Connector\Product\Responser
{
    // ########################################

    protected function getSuccessfulMessage(\Ess\M2ePro\Model\Listing\Product $listingProduct)
    {
        // M2ePro\TRANSLATIONS
        // Item was successfully Listed
        return 'Item was successfully Listed';
    }

    // ########################################

    protected function inspectProducts()
    {
        parent::inspectProducts();

        $childListingProducts = array();

        foreach ($this->successfulListingProducts as $listingProduct) {

            /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
            $amazonListingProduct = $listingProduct->getChildObject();

            if (!$amazonListingProduct->getVariationManager()->isRelationParentType()) {
                continue;
            }

            $childListingProducts = array_merge(
                $childListingProducts,
                $amazonListingProduct->getVariationManager()->getTypeModel()->getChildListingsProducts()
            );
        }

        if (empty($childListingProducts)) {
            return;
        }

        $runner = $this->modelFactory->getObject('Synchronization\Templates\Synchronization\Runner');
        $runner->setConnectorModel('Amazon\Connector\Product\Dispatcher');
        $runner->setMaxProductsPerStep(100);

        $inspector = $this->modelFactory->getObject('Amazon\Synchronization\Templates\Synchronization\Inspector');

        foreach ($childListingProducts as $listingProduct) {

            if (!$inspector->isMeetListRequirements($listingProduct)) {
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

    protected function processSuccess(\Ess\M2ePro\Model\Listing\Product $listingProduct, array $params = array())
    {
        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
        $amazonListingProduct = $listingProduct->getChildObject();

        if ($amazonListingProduct->getVariationManager()->isRelationMode() &&
            !$this->getRequestDataObject($listingProduct)->hasProductId() &&
            empty($params['general_id'])
        ) {
            $message = $this->modelFactory->getObject('Connector\Connection\Response\Message');
            $message->initFromPreparedData(
                'Unexpected error. The ASIN/ISBN for Parent or Child Product was not returned from Amazon.
                 Operation cannot be finished correctly.',
               \Ess\M2ePro\Model\Connector\Connection\Response\Message::TYPE_ERROR
            );

            $this->getLogger()->logListingProductMessage(
                $listingProduct,
                $message,
                \Ess\M2ePro\Model\Log\AbstractLog::PRIORITY_MEDIUM
            );

            return;
        }

        parent::processSuccess($listingProduct, $params);
    }

    protected function getSuccessfulParams(\Ess\M2ePro\Model\Listing\Product $listingProduct)
    {
        $responseData = $this->getPreparedResponseData();

        if (!is_array($responseData['asins']) || empty($responseData['asins'])) {
            return array();
        }

        foreach ($responseData['asins'] as $key => $asin) {
            if ((int)$key != (int)$listingProduct->getId()) {
                continue;
            }

            return array('general_id' => $asin);
        }

        return array();
    }

    // ########################################
}
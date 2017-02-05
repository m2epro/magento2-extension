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

use Ess\M2ePro\Model\Amazon\Synchronization\Templates\Synchronization\Inspector;
use Ess\M2ePro\Model\Synchronization\Templates\Synchronization\Runner;
use Ess\M2ePro\Model\Amazon\Template\Synchronization as SynchronizationPolicy;

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

        $this->inspectListRequirements($childListingProducts, $inspector, $runner);

        $runner->execute();
    }

    protected function inspectListRequirements(array $products, Inspector $inspector, Runner $runner)
    {
        $lpForAdvancedRules = [];

        foreach ($products as $listingProduct) {
            /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */

            if (!$inspector->isMeetListRequirements($listingProduct)) {
                continue;
            }

            /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
            $amazonListingProduct = $listingProduct->getChildObject();
            $amazonTemplate = $amazonListingProduct->getAmazonSynchronizationTemplate();

            if ($amazonTemplate->isListAdvancedRulesEnabled()) {

                $templateId = $amazonTemplate->getId();
                $storeId    = $listingProduct->getListing()->getStoreId();
                $magentoProductId = $listingProduct->getProductId();

                $lpForAdvancedRules[$templateId][$storeId][$magentoProductId][] = $listingProduct;

            } else {

                $runner->addProduct(
                    $listingProduct,
                    \Ess\M2ePro\Model\Listing\Product::ACTION_LIST,
                    $this->modelFactory->getObject('Amazon\Listing\Product\Action\Configurator')
                );
            }
        }

        $affectedListingProducts = $inspector->getMeetAdvancedRequirementsProducts(
            $lpForAdvancedRules, SynchronizationPolicy::LIST_ADVANCED_RULES_PREFIX, 'list'
        );

        foreach ($affectedListingProducts as $listingProduct) {
            /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */

            $runner->addProduct(
                $listingProduct,
                \Ess\M2ePro\Model\Listing\Product::ACTION_LIST,
                $this->modelFactory->getObject('Amazon\Listing\Product\Action\Configurator')
            );
        }
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
                \Ess\M2ePro\Model\Log\AbstractModel::PRIORITY_MEDIUM
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
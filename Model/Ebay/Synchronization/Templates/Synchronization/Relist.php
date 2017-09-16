<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Synchronization\Templates\Synchronization;

use Ess\M2ePro\Model\Ebay\Template\Synchronization as SynchronizationPolicy;

class Relist extends AbstractModel
{
    private $cacheConfig;

    //########################################

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\Config\Manager\Cache $cacheConfig
    ) {
        parent::__construct($resourceConnection, $ebayFactory, $activeRecordFactory, $helperFactory, $modelFactory);

        $this->cacheConfig = $cacheConfig;
    }

    //########################################

    /**
     * @return string
     */
    protected function getNick()
    {
        return '/synchronization/relist/';
    }

    /**
     * @return string
     */
    protected function getTitle()
    {
        return 'Relist';
    }

    // ---------------------------------------

    /**
     * @return int
     */
    protected function getPercentsStart()
    {
        return 35;
    }

    /**
     * @return int
     */
    protected function getPercentsEnd()
    {
        return 55;
    }

    //########################################

    protected function performActions()
    {
        $this->immediatelyChangedProducts();
    }

    //########################################

    private function immediatelyChangedProducts()
    {
        $this->getActualOperationHistory()->addTimePoint(__METHOD__,'Immediately when Product was changed');

        /** @var \Ess\M2ePro\Model\Listing\Product[] $changedListingsProducts */
        $changedListingsProducts = $this->getProductChangesManager()->getInstances(
            array(\Ess\M2ePro\Model\ProductChange::UPDATE_ATTRIBUTE_CODE)
        );

        $lpForAdvancedRules = [];

        foreach ($changedListingsProducts as $listingProduct) {

            try {
                $action = $this->getAction($listingProduct);

                /** @var $configurator \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Configurator */
                $configurator = $this->modelFactory->getObject('Ebay\Listing\Product\Action\Configurator');
                $this->prepareConfigurator($listingProduct, $configurator, $action);

                $isExistInRunner = $this->getRunner()->isExistProductWithCoveringConfigurator(
                    $listingProduct, $action, $configurator
                );

                if ($isExistInRunner) {
                    continue;
                }

                if (!$this->getInspector()->isMeetRelistRequirements($listingProduct)) {
                    continue;
                }

                /** @var \Ess\M2ePro\Model\Ebay\Listing\Product $ebayListingProduct */
                $ebayListingProduct = $listingProduct->getChildObject();
                $ebayTemplate = $ebayListingProduct->getEbaySynchronizationTemplate();

                if ($ebayTemplate->isRelistAdvancedRulesEnabled()) {

                    $templateId = $ebayTemplate->getId();
                    $storeId    = $listingProduct->getListing()->getStoreId();
                    $magentoProductId  = $listingProduct->getProductId();

                    $lpForAdvancedRules[$templateId][$storeId][$magentoProductId][] = $listingProduct;

                } else {

                    $this->getRunner()->addProduct(
                        $listingProduct, $action, $configurator
                    );
                }

            } catch (\Exception $exception) {

                $this->logError($listingProduct, $exception, false);
                continue;
            }
        }

        $this->processAdvancedConditions($lpForAdvancedRules);

        $this->getActualOperationHistory()->saveTimePoint(__METHOD__);
    }

    //########################################

    private function processAdvancedConditions($lpForAdvancedRules)
    {
        $affectedListingProducts = [];

        try {

            $affectedListingProducts = $this->getInspector()->getMeetAdvancedRequirementsProducts(
                $lpForAdvancedRules, SynchronizationPolicy::RELIST_ADVANCED_RULES_PREFIX, 'relist'
            );

        } catch (\Exception $exception) {

            foreach ($lpForAdvancedRules as $templateId => $productsByTemplate) {
                foreach ($productsByTemplate as $storeId => $productsByStore) {
                    foreach ($productsByStore as $magentoProductId => $productsByMagentoProduct) {
                        foreach ($productsByMagentoProduct as $lProduct) {
                            $this->logError($lProduct, $exception, false);
                        }
                    }
                }
            }
        }

        foreach ($affectedListingProducts as $listingProduct) {
            /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */

            try {

                $action = $this->getAction($listingProduct);

                /** @var $configurator \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Configurator */
                $configurator = $this->modelFactory->getObject('Ebay\Listing\Product\Action\Configurator');
                $this->prepareConfigurator($listingProduct, $configurator, $action);

                $this->getRunner()->addProduct(
                    $listingProduct, $action, $configurator
                );

            } catch (\Exception $exception) {

                $this->logError($listingProduct, $exception, false);
                continue;
            }
        }
    }

    //########################################

    private function getAction(\Ess\M2ePro\Model\Listing\Product $listingProduct)
    {
        if ($listingProduct->isHidden()) {
            return \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE;
        }

        return \Ess\M2ePro\Model\Listing\Product::ACTION_RELIST;
    }

    private function prepareConfigurator(\Ess\M2ePro\Model\Listing\Product $listingProduct,
                                         \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Configurator $configurator,
                                         $action)
    {
        if ($action != \Ess\M2ePro\Model\Listing\Product::ACTION_RELIST) {
            $configurator->setParams(array('replaced_action' => \Ess\M2ePro\Model\Listing\Product::ACTION_RELIST));
        }

        /** @var \Ess\M2ePro\Model\Ebay\Listing\Product $ebayListingProduct */
        $ebayListingProduct = $listingProduct->getChildObject();

        if (!$ebayListingProduct->getEbaySynchronizationTemplate()->isRelistSendData()) {
            $configurator->reset();
            $configurator->allowQty();
            $configurator->allowPrice();
            $configurator->allowVariations();
        }
    }

    //########################################
}
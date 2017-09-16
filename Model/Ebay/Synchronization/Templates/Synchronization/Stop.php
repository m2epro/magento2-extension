<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Synchronization\Templates\Synchronization;

use Ess\M2ePro\Model\Ebay\Template\Synchronization as SynchronizationPolicy;

class Stop extends AbstractModel
{
    private $magentoProductCollectionFactory;

    //########################################

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $magentoProductCollectionFactory
    ) {
        parent::__construct($resourceConnection, $ebayFactory, $activeRecordFactory, $helperFactory, $modelFactory);

        $this->magentoProductCollectionFactory = $magentoProductCollectionFactory;
    }

    //########################################

    /**
     * @return string
     */
    protected function getNick()
    {
        return '/synchronization/stop/';
    }

    /**
     * @return string
     */
    protected function getTitle()
    {
        return 'Stop';
    }

    // ---------------------------------------

    /**
     * @return int
     */
    protected function getPercentsStart()
    {
        return 55;
    }

    /**
     * @return int
     */
    protected function getPercentsEnd()
    {
        return 80;
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

                if (!$this->getInspector()->isMeetStopGeneralRequirements($listingProduct)) {
                    continue;
                }

                if ($this->getInspector()->isMeetStopRequirements($listingProduct)) {

                    $this->getRunner()->addProduct(
                        $listingProduct, $action, $configurator
                    );
                    continue;
                }

                /** @var \Ess\M2ePro\Model\Ebay\Listing\Product $ebayListingProduct */
                $ebayListingProduct = $listingProduct->getChildObject();
                $ebayTemplate = $ebayListingProduct->getEbaySynchronizationTemplate();

                if ($ebayTemplate->isStopAdvancedRulesEnabled()) {

                    $templateId = $ebayTemplate->getId();
                    $storeId    = $listingProduct->getListing()->getStoreId();
                    $magentoProductId  = $listingProduct->getProductId();

                    $lpForAdvancedRules[$templateId][$storeId][$magentoProductId][] = $listingProduct;
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
                $lpForAdvancedRules, SynchronizationPolicy::STOP_ADVANCED_RULES_PREFIX, 'stop'
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
        /** @var \Ess\M2ePro\Model\Ebay\Listing\Product $ebayListingProduct */
        $ebayListingProduct = $listingProduct->getChildObject();

        if (!$ebayListingProduct->isOutOfStockControlEnabled()) {
            return \Ess\M2ePro\Model\Listing\Product::ACTION_STOP;
        }

        return \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE;
    }

    private function prepareConfigurator(\Ess\M2ePro\Model\Listing\Product $listingProduct,
                                         \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Configurator $configurator,
                                         $action)
    {
        if ($action != \Ess\M2ePro\Model\Listing\Product::ACTION_STOP) {
            $configurator->setParams(array('replaced_action' => \Ess\M2ePro\Model\Listing\Product::ACTION_STOP));
        }

        /** @var \Ess\M2ePro\Model\Ebay\Listing\Product $ebayListingProduct */
        $ebayListingProduct = $listingProduct->getChildObject();

        if (!$ebayListingProduct->isOutOfStockControlEnabled() &&
            $action == \Ess\M2ePro\Model\Listing\Product::ACTION_STOP
        ) {
            return;
        }

        $configurator->reset();
        $configurator->allowQty();
        $configurator->allowVariations();
    }

    //########################################
}
<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Synchronization\Templates\Synchronization;

final class Stop extends AbstractModel
{
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
        return 80;
    }

    /**
     * @return int
     */
    protected function getPercentsEnd()
    {
        return 100;
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

        foreach ($changedListingsProducts as $listingProduct) {

            try {
                $action = $this->getAction($listingProduct);

                /** @var $configurator \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Configurator */
                $configurator = $this->modelFactory->getObject('Ebay\Listing\Product\Action\Configurator');

                $this->prepareConfigurator($listingProduct, $configurator, $action);

                $isExistInRunner = $this->getRunner()->isExistProduct(
                    $listingProduct, $action, $configurator
                );

                if ($isExistInRunner) {
                    continue;
                }

                if (!$this->getInspector()->isMeetStopRequirements($listingProduct)) {
                    continue;
                }

                $this->getRunner()->addProduct(
                    $listingProduct, $action, $configurator
                );
            } catch (\Exception $exception) {

                $this->logError($listingProduct, $exception);
                continue;
            }
        }

        $this->getActualOperationHistory()->saveTimePoint(__METHOD__);
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

        $configurator->setPartialMode();
        $configurator->allowQty()->allowVariations();
    }

    //########################################
}
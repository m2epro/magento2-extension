<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Synchronization\Templates\Synchronization;

final class Relist extends \Ess\M2ePro\Model\Amazon\Synchronization\Templates\Synchronization\AbstractModel
{
    //########################################

    protected function getNick()
    {
        return '/synchronization/relist/';
    }

    protected function getTitle()
    {
        return 'Relist';
    }

    // ---------------------------------------

    protected function getPercentsStart()
    {
        return 40;
    }

    protected function getPercentsEnd()
    {
        return 65;
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

                /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
                $amazonListingProduct = $listingProduct->getChildObject();

                $amazonSynchronizationTemplate = $amazonListingProduct->getAmazonSynchronizationTemplate();

                $configurator = $this->modelFactory->getObject('Amazon\Listing\Product\Action\Configurator');

                if (!$amazonSynchronizationTemplate->isRelistSendData()) {
                    $configurator->setPartialMode();
                    $configurator->allowQty();
                }

                $isExistInRunner = $this->getRunner()->isExistProduct(
                    $listingProduct, \Ess\M2ePro\Model\Listing\Product::ACTION_RELIST, $configurator
                );

                if ($isExistInRunner) {
                    continue;
                }

                if (!$this->getInspector()->isMeetRelistRequirements($listingProduct)) {
                    continue;
                }

                $this->getRunner()->addProduct(
                    $listingProduct, \Ess\M2ePro\Model\Listing\Product::ACTION_RELIST, $configurator
                );

            } catch (\Exception $exception) {

                $this->logError($listingProduct, $exception);
                continue;
            }
        }

        $this->getActualOperationHistory()->saveTimePoint(__METHOD__);
    }

    //########################################
}
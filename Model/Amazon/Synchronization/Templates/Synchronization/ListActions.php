<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Synchronization\Templates\Synchronization;

final class ListActions extends \Ess\M2ePro\Model\Amazon\Synchronization\Templates\Synchronization\AbstractModel
{
    //########################################

    protected function getNick()
    {
        return '/synchronization/list/';
    }

    protected function getTitle()
    {
        return 'List';
    }

    // ---------------------------------------

    protected function getPercentsStart()
    {
        return 0;
    }

    protected function getPercentsEnd()
    {
        return 15;
    }

    //########################################

    protected function performActions()
    {
        $this->immediatelyChangedProducts();
        $this->immediatelyNotCheckedProducts();
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

                $configurator = $this->modelFactory->getObject('Amazon\Listing\Product\Action\Configurator');

                $isExistInRunner = $this->getRunner()->isExistProduct(
                    $listingProduct, \Ess\M2ePro\Model\Listing\Product::ACTION_LIST, $configurator
                );

                if ($isExistInRunner) {
                    continue;
                }

                if (!$this->getInspector()->isMeetListRequirements($listingProduct)) {
                    continue;
                }

                $this->getRunner()->addProduct(
                    $listingProduct, \Ess\M2ePro\Model\Listing\Product::ACTION_LIST, $configurator
                );

                $this->setListAttemptData($listingProduct);

            } catch (\Exception $exception) {

                $this->logError($listingProduct, $exception);
                continue;
            }
        }

        $this->getActualOperationHistory()->saveTimePoint(__METHOD__);
    }

    private function immediatelyNotCheckedProducts()
    {
        $this->getActualOperationHistory()->addTimePoint(__METHOD__,'Immediately when Product was not checked');

        /** @var $collection \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection */
        $collection = $this->amazonFactory->getObject('Listing\Product')->getCollection();
        $collection->addFieldToFilter('status', \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED);
        $collection->addFieldToFilter('tried_to_list',0);

        $collection->getSelect()->limit(100);

        $listingsProducts = $collection->getItems();

        foreach ($listingsProducts as $listingProduct) {

            /** @var $listingProduct \Ess\M2ePro\Model\Listing\Product */

            try {

                $listingProduct->getMagentoProduct()->enableCache();
                $listingProduct->setData('tried_to_list',1)->save();

                $configurator = $this->modelFactory->getObject('Amazon\Listing\Product\Action\Configurator');

                $isExistInRunner = $this->getRunner()->isExistProduct(
                    $listingProduct, \Ess\M2ePro\Model\Listing\Product::ACTION_LIST, $configurator
                );

                if ($isExistInRunner) {
                    continue;
                }

                if (!$this->getInspector()->isMeetListRequirements($listingProduct)) {
                    continue;
                }

                $this->getRunner()->addProduct(
                    $listingProduct, \Ess\M2ePro\Model\Listing\Product::ACTION_LIST, $configurator
                );

                $this->setListAttemptData($listingProduct);

            } catch (\Exception $exception) {

                $this->logError($listingProduct, $exception);
                continue;
            }
        }

        $this->getActualOperationHistory()->saveTimePoint(__METHOD__);
    }

    //########################################

    private function setListAttemptData(\Ess\M2ePro\Model\Listing\Product $listingProduct)
    {
        $additionalData = $listingProduct->getAdditionalData();
        $additionalData['last_list_attempt_date'] = $this->getHelper('Data')->getCurrentGmtDate();
        $listingProduct->setSettings('additional_data', $additionalData);

        $listingProduct->save();
    }

    //########################################
}
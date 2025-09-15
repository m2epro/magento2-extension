<?php

declare(strict_types=1);

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\AllItems\Actions;

class RunStopAndRemoveProducts extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\ActionAbstract
{
    public function execute()
    {
        $listingsProducts = $this->getListingProductsFromRequest();
        if (empty($listingsProducts)) {
            return $this->setRawContent('You should select Products');
        }

        $logsActionId = $this->getNextLogActionId();

        $this->checkLocking($listingsProducts, $logsActionId, \Ess\M2ePro\Model\Listing\Product::ACTION_STOP);
        if (empty($listingsProducts)) {
            $this->setJsonContent(['result' => 'error', 'action_id' => $logsActionId]);

            return $this->getResult();
        }

        foreach ($listingsProducts as $index => $listingProduct) {
            if (!$listingProduct->isStoppable()) {
                /** @var \Ess\M2ePro\Model\Amazon\Listing\Product\RemoveHandler $removeHandler */
                $removeHandler = $this->modelFactory->getObject('Amazon_Listing_Product_RemoveHandler');
                $removeHandler->setListingProduct($listingProduct);
                $removeHandler->process();

                unset($listingsProducts[$index]);
            }
        }

        if (empty($listingsProducts)) {
            $this->setJsonContent(['result' => 'success', 'action_id' => $logsActionId]);

            return $this->getResult();
        }

        $this->createUpdateScheduledActions(
            $listingsProducts,
            \Ess\M2ePro\Model\Listing\Product::ACTION_STOP,
            ['remove' => true]
        );

        $this->setJsonContent(['result' => 'success', 'action_id' => $logsActionId]);

        return $this->getResult();
    }
}

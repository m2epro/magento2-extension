<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Listing\Moving;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Listing\Moving\MoveToListing
 */
class MoveToListing extends \Ess\M2ePro\Controller\Adminhtml\Listing
{
    //########################################

    public function execute()
    {
        $sessionHelper = $this->getHelper('Data\Session');

        $componentMode = $this->getRequest()->getParam('componentMode');
        $sessionKey = $componentMode . '_' . \Ess\M2ePro\Helper\View::MOVING_LISTING_PRODUCTS_SELECTED_SESSION_KEY;
        $selectedProducts = $sessionHelper->getValue($sessionKey);

        /** @var \Ess\M2ePro\Model\Listing $targetListing */
        $sourceListing = null;
        $targetListing = $this->parentFactory->getCachedObjectLoaded(
            $componentMode,
            'Listing',
            (int)$this->getRequest()->getParam('listingId')
        );

        $variationUpdaterModel = ucwords($targetListing->getComponentMode())
            .'\Listing\Product\Variation\Updater';

        /** @var \Ess\M2ePro\Model\Listing\Product\Variation\Updater $variationUpdaterObject */
        $variationUpdaterObject = $this->modelFactory->getObject($variationUpdaterModel);
        $variationUpdaterObject->beforeMassProcessEvent();

        $errorsCount = 0;
        foreach ($selectedProducts as $listingProductId) {

            /** @var \Ess\M2ePro\Model\Listing\Product $listingProductInstance */
            $listingProduct = $this->parentFactory
                ->getObjectLoaded($componentMode, 'Listing\Product', $listingProductId);

            $sourceListing = $listingProduct->getListing();

            if (!$targetListing->getChildObject()->addProductFromListing($listingProduct, $sourceListing)) {
                $errorsCount++;
                continue;
            }

            if ($targetListing->getStoreId() != $sourceListing->getStoreId()) {
                $variationUpdaterObject->process($listingProduct);
            }
        }

        $variationUpdaterObject->afterMassProcessEvent();
        $sessionHelper->removeValue($sessionKey);

        if ($errorsCount) {

            $logViewUrl = $this->getUrl(
                '*/' . $componentMode . '_log_listing_product/index',
                [
                    'id' => $sourceListing->getId(),
                    'back'=>$this->getHelper('Data')
                    ->makeBackUrlParam('*/' . $componentMode . '_listing/view', ['id' => $sourceListing->getId()])
                ]
            );

            if (count($selectedProducts) == $errorsCount) {

                $this->setJsonContent(
                    [
                        'result'  => false,
                        'message' => $this->__(
                            'Products were not Moved. <a target="_blank" href="%url%">View Log</a> for details.',
                            $logViewUrl
                        )
                    ]
                );

                return $this->getResult();
            }

            $this->setJsonContent(
                [
                    'result'   => true,
                    'isFailed' => true,
                    'message'  => $this->__(
                        '%errors_count% product(s) were not Moved.
                        Please <a target="_blank" href="%url%">view Log</a> for the details.',
                        $errorsCount,
                        $logViewUrl
                    )
                ]
            );

        } else {
            $this->setJsonContent(
                [
                    'result'  => true,
                    'message' => $this->__('Product(s) was Moved.')
                ]
            );
        }

        return $this->getResult();
    }

    //########################################
}

<?php

namespace Ess\M2ePro\Controller\Adminhtml\Listing\Moving;

use Ess\M2ePro\Model\Ebay\Listing\Product\Variation\Updater as EbayVariationUpdater;
use Ess\M2ePro\Model\Amazon\Listing\Product\Variation\Updater as AmazonVariationUpdater;
use Ess\M2ePro\Model\Walmart\Listing\Product\Variation\Updater as WalmartVariationUpdater;

class MoveToListing extends \Ess\M2ePro\Controller\Adminhtml\Listing
{
    /** @var \Ess\M2ePro\Helper\Data\Session */
    private $sessionHelper;

    /** @var \Ess\M2ePro\Helper\Data */
    private $dataHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Data\Session $sessionHelper,
        \Ess\M2ePro\Helper\Data $dataHelper,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($context);

        $this->sessionHelper = $sessionHelper;
        $this->dataHelper = $dataHelper;
    }

    public function execute()
    {
        $componentMode = $this->getRequest()->getParam('componentMode');
        $sessionKey = $componentMode . '_' . \Ess\M2ePro\Helper\View::MOVING_LISTING_PRODUCTS_SELECTED_SESSION_KEY;
        $selectedProducts = $this->sessionHelper->getValue($sessionKey);

        /** @var \Ess\M2ePro\Model\Listing $targetListing */
        $sourceListing = null;
        $targetListing = $this->parentFactory->getCachedObjectLoaded(
            $componentMode,
            'Listing',
            (int)$this->getRequest()->getParam('listingId')
        );

        $variationUpdaterModel = ucwords($targetListing->getComponentMode())
            . '\Listing\Product\Variation\Updater';

        /** @var EbayVariationUpdater|AmazonVariationUpdater|WalmartVariationUpdater $variationUpdaterObject */
        $variationUpdaterObject = $this->modelFactory->getObject($variationUpdaterModel);

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
        $this->sessionHelper->removeValue($sessionKey);

        if ($errorsCount) {
            $logViewUrl = $this->getUrl(
                '*/' . $componentMode . '_log_listing_product/index',
                [
                    'id' => $sourceListing->getId(),
                    'back' => $this->dataHelper
                        ->makeBackUrlParam('*/' . $componentMode . '_listing/view', ['id' => $sourceListing->getId()]),
                ]
            );

            if (count($selectedProducts) == $errorsCount) {
                $this->setJsonContent(
                    [
                        'result' => false,
                        'message' => $this->__(
                            'Products were not Moved. <a target="_blank" href="%url%">View Log</a> for details.',
                            $logViewUrl
                        ),
                    ]
                );

                return $this->getResult();
            }

            $this->setJsonContent(
                [
                    'result' => true,
                    'isFailed' => true,
                    'message' => $this->__(
                        '%errors_count% product(s) were not Moved.
                        Please <a target="_blank" href="%url%">view Log</a> for the details.',
                        $errorsCount,
                        $logViewUrl
                    ),
                ]
            );
        } else {
            $this->setJsonContent(
                [
                    'result' => true,
                    'message' => $this->__('Product(s) was Moved.'),
                ]
            );
        }

        return $this->getResult();
    }
}

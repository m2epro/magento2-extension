<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Listing\Wizard;

use Ess\M2ePro\Model\Ebay\Listing\Wizard\Product as WizardProduct;
use Ess\M2ePro\Model\Ebay\Listing\Wizard\Manager as WizardManager;
use Ess\M2ePro\Model\Listing\Product as ListingProduct;
use Ess\M2ePro\Model\Listing;
use Ess\M2ePro\Model\ActiveRecord\Factory as ActiveRecordFactory;
use Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory as EbayFactory;

class CompleteProcessor
{
    private ActiveRecordFactory $activeRecordFactory;

    private ValidationErrorsProcessor $validationErrorsProcessor;

    private EbayFactory $ebayFactory;

    public function __construct(
        ActiveRecordFactory $activeRecordFactory,
        ValidationErrorsProcessor $validationErrorsProcessor,
        EbayFactory $ebayFactory
    ) {
        $this->activeRecordFactory = $activeRecordFactory;
        $this->validationErrorsProcessor = $validationErrorsProcessor;
        $this->ebayFactory = $ebayFactory;
    }

    public function process(Manager $wizardManager): array
    {
        $listing = $wizardManager->getListing();

        $processedWizardProductIds = [];
        $listingProducts = [];

        /**
         * @var WizardProduct $wizardProduct
         */
        foreach ($wizardManager->getNotProcessedProducts() as $wizardProduct) {
            $listingProduct = $this->addProduct($wizardManager, $wizardProduct, $listing);

            if ($listingProduct === null) {
                continue;
            }

            $this->validationErrorsProcessor->process($listingProduct, $wizardProduct);

            /**
             * Covers case when user is allowed to add to a listing product not having primary category assigned
             */
            if ($wizardProduct->getTemplateCategoryId() || $wizardProduct->getStoreCategoryId()) {
                $this->activeRecordFactory->getObject('Ebay_Listing_Product')
                                          ->assignTemplatesToProducts(
                                              [$listingProduct ->getId()],
                                              $wizardProduct->getTemplateCategoryId(),
                                              $wizardProduct->getTemplateCategorySecondaryId(),
                                              $wizardProduct->getStoreCategoryId(),
                                              $wizardProduct->getStoreCategorySecondaryId()
                                          );
            }

            $processedWizardProductIds[] = $wizardProduct->getId();
            $listingProducts[] = $listingProduct;
        }

        if (!empty($processedWizardProductIds)) {
            $wizardManager->markProductsAsProcessed($processedWizardProductIds);
        }

        return $listingProducts;
    }

    private function addProduct(
        WizardManager $wizardManager,
        WizardProduct $wizardProduct,
        Listing $listing
    ): ?ListingProduct {
        /**
         * @var ListingProduct $listingProduct
         */
        $listingProduct = null;

        if ($wizardManager->isWizardTypeGeneral()) {
            $listingProduct = $listing
                ->addProduct(
                    $wizardProduct->getMagentoProductId(),
                    \Ess\M2ePro\Helper\Data::INITIATOR_USER
                );

            if (!$listingProduct) {
                return null;
            }
        }

        if ($wizardManager->isWizardTypeUnmanaged()) {
            $listingOther = $this->ebayFactory->getObjectLoaded(
                'Listing\Other',
                $wizardProduct->getUnmanagedProductId()
            );

            if (!$listingOther->getProductId() || !$listingOther->getMagentoProduct()->exists()) {
                return null;
            }

            //@todo refactor to use service instead of method in the model
            /** @var \Ess\M2ePro\Model\Ebay\Listing\Product $listingProduct */
            $listingProduct = $listing->getChildObject()->addProductFromOther(
                $listingOther,
                \Ess\M2ePro\Helper\Data::INITIATOR_USER
            );

            if (!($listingProduct instanceof \Ess\M2ePro\Model\Listing\Product)) {
                return null;
            }

            //@todo refactor to use service instead of method in the model
            $listingOther->moveToListingSucceed();
        }

        return $listingProduct;
    }
}

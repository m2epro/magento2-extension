<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Synchronization\Templates;

use Ess\M2ePro\Model\ProductChange;

/**
 * Class \Ess\M2ePro\Model\Amazon\Synchronization\Templates\Repricing
 */
class Repricing extends AbstractModel
{
    //########################################

    protected function getNick()
    {
        return '/repricing/update/';
    }

    protected function getTitle()
    {
        return 'Repricing';
    }

    // ---------------------------------------

    protected function getPercentsStart()
    {
        return 0;
    }

    protected function getPercentsEnd()
    {
        return 20;
    }

    protected function isPossibleToRun()
    {
        if (!parent::isPossibleToRun()) {
            return false;
        }

        return $this->getHelper('Component_Amazon_Repricing')->isEnabled();
    }

    //########################################

    protected function performActions()
    {
        $changedListingsProductsRepricing = $this->getChangedListingsProductsRepricing();
        if (empty($changedListingsProductsRepricing)) {
            return;
        }

        $processRequiredListingsProductsIds      = [];
        $resetProcessRequiredListingsProductsIds = [];

        foreach ($changedListingsProductsRepricing as $listingProductRepricing) {
            try {
                if ($this->isProcessRequired($listingProductRepricing)) {
                    $processRequiredListingsProductsIds[] = $listingProductRepricing->getListingProductId();
                    continue;
                }

                if ($listingProductRepricing->isProcessRequired()) {
                    $resetProcessRequiredListingsProductsIds[] = $listingProductRepricing->getListingProductId();
                }
            } catch (\Exception $exception) {
                $this->logError($listingProductRepricing->getListingProduct(), $exception);
            }
        }

        if (!empty($processRequiredListingsProductsIds)) {
            $this->activeRecordFactory->getObject('Amazon_Listing_Product_Repricing')
                ->getResource()->markAsProcessRequired(array_unique($processRequiredListingsProductsIds));
        }

        if (!empty($resetProcessRequiredListingsProductsIds)) {
            $this->activeRecordFactory->getObject('Amazon_Listing_Product_Repricing')
                ->getResource()->resetProcessRequired(array_unique($resetProcessRequiredListingsProductsIds));
        }
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Amazon\Listing\Product\Repricing[]
     */
    private function getChangedListingsProductsRepricing()
    {
        $changedListingsProducts = $this->getProductChangesManager()->getInstances(
            [ProductChange::UPDATE_ATTRIBUTE_CODE]
        );

        if (empty($changedListingsProducts)) {
            return [];
        }

        $listingProductRepricingCollection = $this->activeRecordFactory->getObject(
            'Amazon_Listing_Product_Repricing'
        )->getCollection();
        $listingProductRepricingCollection->addFieldToFilter(
            'listing_product_id',
            ['in' => array_keys($changedListingsProducts)]
        );

        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product\Repricing[] $listingsProductsRepricing */
        $listingsProductsRepricing = $listingProductRepricingCollection->getItems();
        if (empty($listingsProductsRepricing)) {
            return [];
        }

        foreach ($listingsProductsRepricing as $listingProductRepricing) {
            $listingProductRepricing->setListingProduct(
                $changedListingsProducts[$listingProductRepricing->getListingProductId()]
            );
        }

        return $listingsProductsRepricing;
    }

    private function isProcessRequired(\Ess\M2ePro\Model\Amazon\Listing\Product\Repricing $listingProductRepricing)
    {
        $isDisabled       = $listingProductRepricing->isDisabled();
        $isOnlineDisabled = $listingProductRepricing->isOnlineDisabled();

        if ($isDisabled && $isOnlineDisabled) {
            return false;
        }

        if ($listingProductRepricing->getRegularPrice() == $listingProductRepricing->getOnlineRegularPrice() &&
            $listingProductRepricing->getMinPrice()     == $listingProductRepricing->getOnlineMinPrice() &&
            $listingProductRepricing->getMaxPrice()     == $listingProductRepricing->getOnlineMaxPrice() &&
            $isDisabled == $isOnlineDisabled
        ) {
            return false;
        }

        return true;
    }

    //########################################
}

<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2016 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Log\Listing\Product;

abstract class AbstractView extends \Ess\M2ePro\Block\Adminhtml\Log\Listing\AbstractView
{
    //########################################

    protected function getFiltersHtml()
    {
        $staticListingFilter = '';

        $listingId = $this->getRequest()->getParam(
            \Ess\M2ePro\Block\Adminhtml\Log\Listing\Product\AbstractGrid::LISTING_ID_FIELD, false
        );
        $listingProductId = $this->getRequest()->getParam(
            \Ess\M2ePro\Block\Adminhtml\Log\Listing\Product\AbstractGrid::LISTING_PRODUCT_ID_FIELD, false
        );

        /** @var \Ess\M2ePro\Model\Listing $listing */
        $listing = null;

        if ($listingId) {
            $listing = $this->activeRecordFactory->getCachedObjectLoaded('Listing', $listingId, null, false);
        }

        if ($listingProductId) {
            /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
            $listingProduct = $this->activeRecordFactory->getObjectLoaded(
                'Listing\Product', $listingProductId, null, false
            );

            if (!is_null($listingProduct)) {
                $listing = $listingProduct->getListing();
                $listingTitle = $this->filterManager->truncate(
                    $listing->getTitle(), ['length' => 15]
                );
                $staticListingFilter = $this->getStaticFilterHtml($this->__('Listing'), $listingTitle);
            }
        }

        if (!is_null($listing)) {

            $accountTitle = $this->filterManager->truncate(
                $listing->getAccount()->getTitle(), ['length' => 15]
            );

            return
                '<div class="static-switcher-block">'
                . $staticListingFilter
                . $this->getStaticFilterHtml(
                    $this->accountSwitcherBlock->getLabel(), $accountTitle
                )
                . $this->getStaticFilterHtml(
                    $this->marketplaceSwitcherBlock->getLabel(), $listing->getMarketplace()->getTitle()
                )
                . '</div>';
        }

        return
              '<div class="switcher-separator"></div>'
            . $this->listingTypeSwitcherBlock->toHtml()
            . $this->accountSwitcherBlock->toHtml()
            . $this->marketplaceSwitcherBlock->toHtml();
    }

    //########################################
}
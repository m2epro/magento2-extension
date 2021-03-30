<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Log\Listing\Product;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Log\Listing\Product\AbstractView
 */
abstract class AbstractView extends \Ess\M2ePro\Block\Adminhtml\Log\Listing\AbstractView
{
    /** @var \Ess\M2ePro\Model\Listing $listing */
    protected $listing;

    /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
    protected $listingProduct;

    //########################################

    protected function getFiltersHtml()
    {
        $sessionViewMode = $this->getHelper('Data\Session')->getValue(
            "{$this->getComponentMode()}_log_listing_view_mode"
        );

        $uniqueMessageFilterBlockHtml = '';
        if ($sessionViewMode == \Ess\M2ePro\Block\Adminhtml\Log\Listing\View\Switcher::VIEW_MODE_SEPARATED) {
            $uniqueMessageFilterBlockHtml = $this->uniqueMessageFilterBlock->toHtml();
        }

        if ($this->getListingId()) {
            $html = $this->getStaticFilterHtml(
                $this->accountSwitcherBlock->getLabel(),
                $this->getListing()->getAccount()->getTitle()
            )
                . $this->getStaticFilterHtml(
                    $this->marketplaceSwitcherBlock->getLabel(),
                    $this->getListing()->getMarketplace()->getTitle()
                )
                . $uniqueMessageFilterBlockHtml;
        } elseif ($this->getListingProductId()) {
            $html = $this->getStaticFilterHtml(
                $this->accountSwitcherBlock->getLabel(),
                $this->getListingProduct()->getListing()->getAccount()->getTitle()
            )
                . $this->getStaticFilterHtml(
                    $this->marketplaceSwitcherBlock->getLabel(),
                    $this->getListingProduct()->getListing()->getMarketplace()->getTitle()
                );
        } else {
            $html = $this->accountSwitcherBlock->toHtml()
                . $this->marketplaceSwitcherBlock->toHtml()
                . $uniqueMessageFilterBlockHtml;
        }

        return
              '<div class="switcher-separator"></div>'
            . $html;
    }

    //########################################

    public function getListingId()
    {
        return $this->getRequest()->getParam(
            \Ess\M2ePro\Block\Adminhtml\Log\Listing\Product\AbstractGrid::LISTING_ID_FIELD,
            false
        );
    }

    /**
     * @return \Ess\M2ePro\Model\Listing
     */
    public function getListing()
    {
        if ($this->listing === null) {
            $this->listing = $this->activeRecordFactory->getCachedObjectLoaded(
                'Listing',
                $this->getListingId(),
                null,
                false
            );
        }

        return $this->listing;
    }

    //########################################

    public function getListingProductId()
    {
        return $this->getRequest()->getParam(
            \Ess\M2ePro\Block\Adminhtml\Log\Listing\Product\AbstractGrid::LISTING_PRODUCT_ID_FIELD,
            false
        );
    }

    /**
     * @return \Ess\M2ePro\Model\Listing\Product
     */
    public function getListingProduct()
    {
        if ($this->listingProduct === null) {
            $this->listingProduct = $this->activeRecordFactory->getObjectLoaded(
                'Listing\Product',
                $this->getListingProductId(),
                null,
                false
            );
        }

        return $this->listingProduct;
    }

    //########################################
}

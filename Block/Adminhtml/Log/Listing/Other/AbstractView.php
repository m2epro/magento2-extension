<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2016 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Log\Listing\Other;

abstract class AbstractView extends \Ess\M2ePro\Block\Adminhtml\Log\Listing\AbstractView
{
    //#######################################

    protected function getFiltersHtml()
    {
        $isListings = $this->getRequest()->getParam('listings', false);

        if ($isListings) {
            $accountTitle = $this->activeRecordFactory->getCachedObjectLoaded(
                'Account', $this->accountSwitcherBlock->getSelectedParam()
            )->getTitle();

            $accountTitle = $this->filterManager->truncate(
                $accountTitle, ['length' => 15]
            );

            $marketplaceTitle = $this->activeRecordFactory->getCachedObjectLoaded(
                'Marketplace', $this->marketplaceSwitcherBlock->getSelectedParam()
            )->getTitle();

            return
                '<div class="static-switcher-block">'
                . $this->getStaticFilterHtml(
                    $this->accountSwitcherBlock->getLabel(), $accountTitle
                )
                . $this->getStaticFilterHtml(
                    $this->marketplaceSwitcherBlock->getLabel(), $marketplaceTitle
                )
                . '</div>';
        }

        $listingId = $this->getRequest()->getParam(
            \Ess\M2ePro\Block\Adminhtml\Log\Listing\Other\AbstractGrid::LISTING_ID_FIELD, false
        );

        /** @var \Ess\M2ePro\Model\Listing\Other $listingOther */
        $listingOther = null;

        if ($listingId) {
            $listingOther = $this->activeRecordFactory->getObjectLoaded('Listing\Other', $listingId, null, false);
        }

        if (!is_null($listingOther)) {

            $accountTitle = $this->filterManager->truncate(
                $listingOther->getAccount()->getTitle(), ['length' => 15]
            );

            return
                '<div class="static-switcher-block">'
                . $this->getStaticFilterHtml(
                    $this->accountSwitcherBlock->getLabel(), $accountTitle
                )
                . $this->getStaticFilterHtml(
                    $this->marketplaceSwitcherBlock->getLabel(), $listingOther->getMarketplace()->getTitle()
                )
                . '</div>';
        }

        return
              '<div class="switcher-separator"></div>'
            . $this->listingTypeSwitcherBlock->toHtml()
            . $this->accountSwitcherBlock->toHtml()
            . $this->marketplaceSwitcherBlock->toHtml();
    }

    //#######################################
}
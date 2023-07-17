<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing;

class Index extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing
{
    /**
     * @inheritDoc
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Ess_M2ePro::amazon_listings_m2epro');
    }

    public function execute()
    {
        if ($this->isAjax()) {
            /** @var \Ess\M2ePro\Block\Adminhtml\Amazon\Listing\ItemsByListing\Grid $grid */
            $grid = $this->getLayout()->createBlock(
                \Ess\M2ePro\Block\Adminhtml\Amazon\Listing\ItemsByListing\Grid::class
            );
            $this->setAjaxContent($grid);

            return $this->getResult();
        }

        /** @var \Ess\M2ePro\Block\Adminhtml\Amazon\Listing\ItemsByListing $block */
        $block = $this->getLayout()->createBlock(
            \Ess\M2ePro\Block\Adminhtml\Amazon\Listing\ItemsByListing::class
        );

        $this->addContent($block);

        $this->getResultPage()->getConfig()->getTitle()->prepend($this->__('Items By Listing'));
        $this->setPageHelpLink('m2e-pro-listings');

        return $this->getResult();
    }
}

<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing;

class Index extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing
{
    /**
     * @ingeritdoc
     */
    public function execute()
    {
        if ($this->isAjax()) {
            /** @var \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\ItemsByListing\Grid $grid */
            $grid = $this->getLayout()->createBlock(
                \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\ItemsByListing\Grid::class
            );
            $this->setAjaxContent($grid);

            return $this->getResult();
        }

        /** @var \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\ItemsByListing $block */
        $block = $this->getLayout()->createBlock(
            \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\ItemsByListing::class
        );
        $this->addContent($block);

        $this->getResultPage()->getConfig()->getTitle()->prepend(__('Items By Listing'));
        $this->setPageHelpLink('x/Fv8UB');

        return $this->getResult();
    }
}

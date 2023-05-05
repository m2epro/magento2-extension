<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Listing;

use Ess\M2ePro\Block\Adminhtml\Walmart\Listing\ItemsByListing;

class Index extends \Ess\M2ePro\Controller\Adminhtml\Walmart\Listing
{
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Ess_M2ePro::walmart_listings_m2epro');
    }

    public function execute()
    {
        if ($this->getRequest()->getQuery('ajax')) {
            $this->setAjaxContent(
                $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Walmart\Listing\ItemsByListing\Grid::class)
            );

            return $this->getResult();
        }

        $this->addContent($this->getLayout()->createBlock(ItemsByListing::class));
        $this->getResultPage()->getConfig()->getTitle()->prepend($this->__('Items By Listing'));
        $this->setPageHelpLink('x/Wv1IB ');

        return $this->getResult();
    }
}

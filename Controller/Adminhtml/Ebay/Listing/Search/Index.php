<?php

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Search;

class Index extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing
{
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Ess_M2ePro::ebay_listings_search');
    }

    public function execute()
    {
        if ($this->getRequest()->getQuery('ajax')) {
            $this->setAjaxContent(
                $this->createBlock('Ebay\Listing\Search\Grid')
            );
            return $this->getResult();
        }

        $this->addContent($this->createBlock('Ebay\Listing\Search'));
        $this->getResultPage()->getConfig()->getTitle()->prepend($this->__('Search Products'));
        $this->setPageHelpLink('x/6gEtAQ');

        return $this->getResult();
    }
}
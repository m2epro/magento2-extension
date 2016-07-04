<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Search;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Main;

class Index extends Main
{
    //########################################

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Ess_M2ePro::amazon_listings_search');
    }

    //########################################

    public function execute()
    {
        if ($this->getRequest()->getQuery('ajax')) {
            $this->setAjaxContent(
                $this->createBlock('Amazon\Listing\Search\Grid')
            );
            return $this->getResult();
        }

        $this->addContent($this->createBlock('Amazon\Listing\Search'));
        $this->getResultPage()->getConfig()->getTitle()->prepend($this->__('Search Products'));
        $this->setComponentPageHelpLink('Search+Products');

        return $this->getResult();
    }

    //########################################
}
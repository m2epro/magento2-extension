<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Index
 */
class Index extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing
{
    //########################################

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Ess_M2ePro::amazon_listings_m2epro');
    }

    //########################################

    public function execute()
    {
        if ($this->getRequest()->getQuery('ajax')) {
            $this->setAjaxContent(
                $this->createBlock('Amazon_Listing_Grid')
            );
            return $this->getResult();
        }

        $this->addContent($this->createBlock('Amazon\Listing'));
        $this->getResultPage()->getConfig()->getTitle()->prepend($this->__('M2E Pro Listings'));
        $this->setPageHelpLink('x/AgItAQ');

        return $this->getResult();
    }

    //########################################
}

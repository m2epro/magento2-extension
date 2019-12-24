<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Index
 */
class Index extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing
{
    public function execute()
    {
        if ($this->getRequest()->getQuery('ajax')) {
            $this->setAjaxContent(
                $this->createBlock('Ebay_Listing_Grid')
            );
            return $this->getResult();
        }

        $this->addContent($this->createBlock('Ebay\Listing'));
        $this->getResultPage()->getConfig()->getTitle()->prepend($this->__('M2E Pro Listings'));
        $this->setPageHelpLink('x/7gEtAQ');

        return $this->getResult();
    }
}

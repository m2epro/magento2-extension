<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Other;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Other\View
 */
class View extends \Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Other
{
    public function execute()
    {
        $accountId = $this->getRequest()->getParam('account');
        $marketplaceId = $this->getRequest()->getParam('marketplace');

        if (empty($accountId) || empty($marketplaceId)) {
            $this->getMessageManager()->addErrorMessage($this->__('You should provide correct parameters.'));

            return $this->_redirect('*/*/index');
        }

        $this->getResultPage()->getConfig()->getTitle()->prepend($this->__('3rd Party Listings'));
        $this->setPageHelpLink('x/UgBhAQ');

        $this->addContent($this->createBlock('Walmart_Listing_Other_View'));

        return $this->getResult();
    }
}

<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Other;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Other\Index
 */
class Index extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Other
{
    public function execute()
    {
        $this->addContent($this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Other::class));
        $this->getResultPage()->getConfig()->getTitle()->prepend($this->__('Unmanaged Listings'));
        $this->setPageHelpLink('x/FP8UB');

        return $this->getResult();
    }
}

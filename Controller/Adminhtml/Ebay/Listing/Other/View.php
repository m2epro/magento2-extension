<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Other;

class View extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Other
{
    public function execute()
    {
        $this->getResultPage()->getConfig()->getTitle()->prepend($this->__('3rd Party Listings'));

        $this->addContent($this->getLayout()->createBlock(
            'Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Other\View'
        ));

        return $this->getResult();
    }
}
<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Other;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Other\Reset
 */
class Reset extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Other
{
    //########################################

    public function execute()
    {
        $this->activeRecordFactory->getObject('Amazon_Listing_Other')->getResource()->resetEntities();

        $this->messageManager->addSuccess($this->__('Amazon Unmanaged Listings were reset.'));

        $this->_redirect($this->_redirect->getRefererUrl());
    }

    //########################################
}

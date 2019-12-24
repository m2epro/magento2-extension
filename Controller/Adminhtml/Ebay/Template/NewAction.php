<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Template;

use Ess\M2ePro\Controller\Adminhtml\Ebay\Template;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Template\NewAction
 */
class NewAction extends Template
{
    //########################################

    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Forward $resultForward */
        $resultForward = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_FORWARD);
        $resultForward->forward('edit');
        return $resultForward;
    }

    //########################################
}

<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Template;

use Ess\M2ePro\Controller\Adminhtml\Base;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Template\SkipDefaultValuesInSyncPolicy
 */
class SkipDefaultValuesInSyncPolicy extends Base
{
    //########################################

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function execute()
    {
        $messages = $this->getHelper('Module')->getUpgradeMessages();
        unset($messages['default_values_in_sync_policy']);

        $this->getHelper('Module')->getRegistry()->setValue('/upgrade/messages/', $messages);

        return $this->_redirect($this->_redirect->getRefererUrl());
    }

    //########################################
}

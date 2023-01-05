<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Repricer;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Account;

class Source extends Account
{
    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function execute()
    {
        if ($this->activeRecordFactory->getObject('Amazon_Account_Repricing')->getCollection()->getSize() > 0) {
            return $this->_redirect('https://repricer.m2epro.com/');
        }

        return $this->_redirect($this->getUrl('*/amazon_repricer_settings/index', ['warning' => true]));
    }
}

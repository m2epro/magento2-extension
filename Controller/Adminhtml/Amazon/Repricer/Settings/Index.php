<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Repricer\Settings;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Repricer;

class Index extends Repricer
{
    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|\Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        if ($this->getRequest()->getParam('warning', false)) {
            $this->getMessageManager()->addWarning(
                $this->__('To start using Repricer, please link at least one Amazon marketplace first.')
            );
        }

        $this->addContent($this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Amazon\Repricer::class));
        $this->getResultPage()->getConfig()->getTitle()->prepend($this->__('Settings'));

        return $this->getResultPage();
    }
}

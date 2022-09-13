<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Synchronization\Log;

class Index extends \Ess\M2ePro\Controller\Adminhtml\Synchronization\Log
{
    public function execute()
    {
        $this->addContent(
            $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Synchronization\Log::class)
        );
        $this->getResultPage()->getConfig()->getTitle()->prepend($this->__('Synchronization Logs'));

        return $this->getResult();
    }
}

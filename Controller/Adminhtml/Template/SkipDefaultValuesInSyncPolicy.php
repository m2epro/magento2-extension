<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Template;

use Ess\M2ePro\Controller\Adminhtml\Base;

class SkipDefaultValuesInSyncPolicy extends Base
{
    /** @var \Ess\M2ePro\Helper\Module */
    private $moduleHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Module $moduleHelper,
        \Ess\M2ePro\Helper\Module\Database\Structure $dbStructureHelper,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($context);

        $this->moduleHelper = $moduleHelper;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function execute()
    {
        $messages = $this->moduleHelper->getUpgradeMessages();
        unset($messages['default_values_in_sync_policy']);

        $this->moduleHelper->getRegistry()->setValue('/upgrade/messages/', $messages);

        return $this->_redirect($this->redirect->getRefererUrl());
    }
}

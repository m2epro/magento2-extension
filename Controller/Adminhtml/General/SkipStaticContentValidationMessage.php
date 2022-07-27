<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\General;

class SkipStaticContentValidationMessage extends  \Ess\M2ePro\Controller\Adminhtml\General
{
    /** @var \Ess\M2ePro\Helper\Module */
    private $moduleHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Module $moduleHelper,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($context);

        $this->moduleHelper = $moduleHelper;
    }

    public function execute()
    {
        if ($this->getRequest()->getParam('skip_message', false)) {
            $this->moduleHelper->getRegistry()->setValue(
                '/global/notification/static_content/skip_for_version/',
                $this->moduleHelper->getPublicVersion()
            );
        }

        $backUrl = base64_decode($this->getRequest()->getParam('back'));
        return $this->_redirect($backUrl);
    }
}

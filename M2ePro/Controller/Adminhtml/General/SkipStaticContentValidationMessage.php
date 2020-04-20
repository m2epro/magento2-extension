<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\General;

use Ess\M2ePro\Controller\Adminhtml\General;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\General\SkipStaticContentValidationMessage
 */
class SkipStaticContentValidationMessage extends General
{
    //########################################

    public function execute()
    {
        if ($this->getRequest()->getParam('skip_message', false)) {
            $this->modelFactory->getObject('Config_Manager_Cache')->setGroupValue(
                '/global/notification/message/',
                'skip_static_content_validation_message',
                $this->getHelper('Module')->getPublicVersion()
            );
        }

        $backUrl = base64_decode($this->getRequest()->getParam('back'));

        return $this->_redirect($backUrl);
    }

    //########################################
}

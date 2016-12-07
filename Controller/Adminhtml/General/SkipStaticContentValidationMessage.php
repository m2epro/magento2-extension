<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\General;

use Ess\M2ePro\Controller\Adminhtml\General;

class SkipStaticContentValidationMessage extends General
{
    //########################################

    public function execute()
    {
        if ($this->getRequest()->getParam('skip_message', false)) {
            $this->modelFactory->getObject('Config\Manager\Cache')->setGroupValue(
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
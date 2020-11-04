<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\HealthStatus;

use Ess\M2ePro\Controller\Adminhtml\HealthStatus;
use Ess\M2ePro\Block\Adminhtml\HealthStatus\Tabs;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\HealthStatus\Save
 */
class Save extends HealthStatus
{
    //########################################

    public function execute()
    {
        $referrer = $this->getRequest()->getParam('referrer', false);
        $postData = $this->getRequest()->getPost()->toArray();

        if (isset($postData['notification_mode'])) {
            $this->getHelper('Module')->getConfig()->setGroupValue(
                '/health_status/notification/',
                'mode',
                (int)$postData['notification_mode']
            );
        }

        if (isset($postData['notification_email'])) {
            $this->getHelper('Module')->getConfig()->setGroupValue(
                '/health_status/notification/',
                'email',
                $postData['notification_email']
            );
        }

        if (isset($postData['notification_level'])) {
            $this->getHelper('Module')->getConfig()->setGroupValue(
                '/health_status/notification/',
                'level',
                (int)$postData['notification_level']
            );
        }

        $this->getMessageManager()->addSuccessMessage($this->__('Settings are saved.'));

        $params = [];
        $params['tab'] = Tabs::TAB_ID_NOTIFICATIONS;
        $referrer && $params['referrer'] = $referrer;

        $this->_redirect('*/*/index', $params);
    }

    //########################################
}

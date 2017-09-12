<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\HealthStatus;

use Ess\M2ePro\Controller\Adminhtml\HealthStatus;
use Ess\M2ePro\Block\Adminhtml\HealthStatus\Tabs;

class Save extends HealthStatus
{
    protected $moduleConfig;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\Config\Manager\Module $moduleConfig,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ){
        $this->moduleConfig = $moduleConfig;
        parent::__construct($context);
    }

    //########################################

    public function execute()
    {
        $referrer = $this->getRequest()->getParam('referrer', false);
        $postData = $this->getRequest()->getPost()->toArray();

        if (isset($postData['notification_mode'])) {

            $this->moduleConfig->setGroupValue(
                '/health_status/notification/', 'mode', (int)$postData['notification_mode']
            );
        }

        if (isset($postData['notification_email'])) {

            $this->moduleConfig->setGroupValue(
                '/health_status/notification/', 'email', $postData['notification_email']
            );
        }

        if (isset($postData['notification_level'])) {

            $this->moduleConfig->setGroupValue(
                '/health_status/notification/', 'level', (int)$postData['notification_level']
            );
        }

        $this->getMessageManager()->addSuccessMessage($this->__('Settings are successfully saved.'));

        $params = [];
        $params['tab'] = Tabs::TAB_ID_NOTIFICATIONS;
        $referrer && $params['referrer'] = $referrer;

        $this->_redirect('*/*/index', $params);
    }

    //########################################
}
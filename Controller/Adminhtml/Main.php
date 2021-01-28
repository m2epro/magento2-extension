<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml;

use Ess\M2ePro\Helper\Module;
use Ess\M2ePro\Helper\Module\License;
use Ess\M2ePro\Model\HealthStatus\Task\Result;
use Ess\M2ePro\Model\Servicing\Dispatcher;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Main
 */
abstract class Main extends Base
{
    private $systemRequirementsChecked = false;

    //########################################

    protected function preDispatch(\Magento\Framework\App\RequestInterface $request)
    {
        if (($preDispatchResult = parent::preDispatch($request)) !== true) {
            return $preDispatchResult;
        }

        $enabledComponents = $this->getHelper('Component')->getEnabledComponentByView($this->getCustomViewNick());

        if ($this->getCustomViewNick() && empty($enabledComponents)) {
            return $this->_redirect('admin/dashboard');
        }

        $this->addNotificationMessages();

        if ($request->isGet() && !$request->isPost() && !$request->isXmlHttpRequest()) {
            try {
                $this->getHelper('Client')->updateLocationData(false);
            } catch (\Exception $exception) {
                $this->getHelper('Module_Exception')->process($exception);
            }

            try {
                /** @var Dispatcher $dispatcher */
                $dispatcher = $this->modelFactory->getObject('Servicing\Dispatcher');
                $dispatcher->process($dispatcher->getFastTasks());
            } catch (\Exception $exception) {
                $this->getHelper('Module_Exception')->process($exception);
            }
        }

        return true;
    }

    //########################################

    protected function initResultPage()
    {
        parent::initResultPage();

        if ($this->isContentLocked()) {
            $this->resultPage->getLayout()->unsetChild('page.wrapper', 'page_content');
            $this->resultPage->getLayout()->unsetChild('header', 'header.inner.left');
            $this->resultPage->getLayout()->unsetChild('header', 'header.inner.right');
        }
    }

    //########################################

    protected function addLeft(\Magento\Framework\View\Element\AbstractBlock $block)
    {
        if ($this->getRequest()->isGet() &&
            !$this->getRequest()->isPost() &&
            !$this->getRequest()->isXmlHttpRequest()) {
            if ($this->isContentLocked()) {
                return $this;
            }
        }

        return parent::addLeft($block);
    }

    protected function addContent(\Magento\Framework\View\Element\AbstractBlock $block)
    {
        if ($this->getRequest()->isGet() &&
            !$this->getRequest()->isPost() &&
            !$this->getRequest()->isXmlHttpRequest()) {
            if ($this->isContentLocked()) {
                return $this;
            }
        }

        if ($this->isContentLockedByWizard()) {
            return $this->addWizardUpgradeNotification();
        }

        return parent::addContent($block);
    }

    // ---------------------------------------

    protected function beforeAddContentEvent()
    {
        $this->appendRequirementsPopup();
        $this->appendMSINotificationPopup();

        parent::beforeAddContentEvent();
    }

    protected function appendMSINotificationPopup()
    {
        if (!$this->getHelper('Magento')->isMSISupportingVersion()) {
            return;
        }

        if ($this->getHelper('Module')->getRegistry()->getValue('/view/msi/popup/shown/')) {
            return;
        }

        $block = $this->createBlock('MsiNotificationPopup');
        $this->getLayout()->setChild('js', $block->getNameInLayout(), '');
    }

    protected function appendRequirementsPopup()
    {
        if ($this->systemRequirementsChecked) {
            return;
        }

        if ($this->getHelper('Module')->getRegistry()->getValue('/view/requirements/popup/closed/')) {
            return;
        }

        $manager = $this->modelFactory->getObject('Requirements\Manager');
        if ($manager->isMeet()) {
            return;
        }

        $block = $this->createBlock('RequirementsPopup');
        $this->getLayout()->setChild('js', $block->getNameInLayout(), '');

        $this->systemRequirementsChecked = true;
    }

    protected function addWizardUpgradeNotification()
    {
        if ($this->isAjax()) {
            return false;
        }
        /** @var Module\Wizard $wizardHelper */
        $wizardHelper = $this->getHelper('Module\Wizard');

        if (!($activeWizard = $wizardHelper->getActiveBlockerWizard($this->getCustomViewNick()))) {
            return false;
        }

        $activeWizardNick = $wizardHelper->getNick($activeWizard);

        if ((bool)$this->getRequest()->getParam('wizard', false) ||
            $this->getRequest()->getControllerName() == 'wizard_'.$activeWizardNick) {
            return false;
        }

        $this->resultPage->getLayout()->unsetChild('page.content', 'page_main_actions');
        /** @var \Magento\Framework\View\Element\AbstractBlock $notificationBlock */
        $notificationBlock = $wizardHelper->createBlock('notification', $activeWizardNick);
        if ($notificationBlock) {
            return parent::addContent($notificationBlock);
        }

        return $this->_redirect('*/wizard_' . $activeWizardNick, ['referrer' => $this->getCustomViewNick()]);
    }

    //########################################

    protected function getCustomViewHelper()
    {
        return $this->getHelper('View')->getViewHelper($this->getCustomViewNick());
    }

    protected function getCustomViewControllerHelper()
    {
        return $this->getHelper('View')->getControllerHelper($this->getCustomViewNick());
    }

    abstract protected function getCustomViewNick();

    protected function addNotificationMessages()
    {
        if ($this->getRequest()->isGet() &&
            !$this->getRequest()->isPost() &&
            !$this->getRequest()->isXmlHttpRequest()
        ) {
            $this->addHealthStatusNotifications();
            $this->addLicenseNotifications();

            if (!$this->addStaticContentNotification()) {
                $this->addStaticContentWarningNotification();
            }

            $this->addNotifications($this->getHelper('Module')->getServerMessages());
            $this->addNotifications($this->getHelper('Module')->getUpgradeMessages());

            $this->addServerMaintenanceInfo();

            $this->addCronErrorMessage();
            $this->getCustomViewControllerHelper()->addMessages();
        }
    }

    // ---------------------------------------

    protected function addStaticContentNotification()
    {
        if (!$this->getHelper('Magento')->isProduction()) {
            return false;
        }

        if (!$this->getHelper('Module')->isStaticContentDeployed()) {
            $this->addExtendedErrorMessage(
                $this->__(
                    '<p>M2E Pro interface cannot work properly and there is no way to work with it correctly,
                    as your Magento is set to the Production Mode and the static content data was not deployed.</p>
                    <p>Thus, to solve this issue you should follow the recommendations provided in this
                    <a href="%url%" target="_blank">article</a> and update the static content data.</p>',
                    $this->getHelper('Module\Support')->getDocumentationArticleUrl('x/ZgM0AQ')
                ),
                self::GLOBAL_MESSAGES_GROUP
            );

            return true;
        }

        return false;
    }

    protected function addHealthStatusNotifications()
    {
        $currentStatus = $this->modelFactory->getObject('HealthStatus\CurrentStatus');
        $notificationSettings = $this->modelFactory->getObject('HealthStatus_Notification_Settings');

        if (!$notificationSettings->isModeExtensionPages()) {
            return false;
        }

        if ($currentStatus->get() < $notificationSettings->getLevel()) {
            return false;
        }

        $messageBuilder = $this->modelFactory->getObject('HealthStatus_Notification_MessageBuilder');

        switch ($currentStatus->get()) {
            case Result::STATE_NOTICE:
                $this->addExtendedNoticeMessage($messageBuilder->build(), self::GLOBAL_MESSAGES_GROUP);
                break;

            case Result::STATE_WARNING:
                $this->addExtendedWarningMessage($messageBuilder->build(), self::GLOBAL_MESSAGES_GROUP);
                break;

            case Result::STATE_CRITICAL:
                $this->addExtendedErrorMessage($messageBuilder->build(), self::GLOBAL_MESSAGES_GROUP);
                break;
        }

        return true;
    }

    protected function addLicenseNotifications()
    {
        $added = false;
        if (!$added && $this->getCustomViewHelper()->isInstallationWizardFinished()) {
            $added = $this->addLicenseActivationNotifications();
        }

        if (!$added && $this->getHelper('Module\License')->getKey()) {
            $added = $this->addLicenseValidationFailNotifications();
        }

        if (!$added && $this->getHelper('Module\License')->getKey()) {
            $added = $this->addLicenseStatusNotifications();
        }

        return $added;
    }

    // ---------------------------------------

    /**
     * @param array $messages
     */
    protected function addNotifications(array $messages)
    {
        foreach ($messages as $message) {
            if (isset($message['text']) && isset($message['type']) && $message['text'] != '') {
                switch ($message['type']) {
                    case \Ess\M2ePro\Helper\Module::MESSAGE_TYPE_ERROR:
                        $this->getMessageManager()->addError(
                            $this->prepareNotificationMessage($message),
                            self::GLOBAL_MESSAGES_GROUP
                        );
                        break;
                    case \Ess\M2ePro\Helper\Module::MESSAGE_TYPE_WARNING:
                        $this->getMessageManager()->addWarning(
                            $this->prepareNotificationMessage($message),
                            self::GLOBAL_MESSAGES_GROUP
                        );
                        break;
                    case \Ess\M2ePro\Helper\Module::MESSAGE_TYPE_SUCCESS:
                        $this->getMessageManager()->addSuccess(
                            $this->prepareNotificationMessage($message),
                            self::GLOBAL_MESSAGES_GROUP
                        );
                        break;
                    case \Ess\M2ePro\Helper\Module::MESSAGE_TYPE_NOTICE:
                    default:
                        $this->getMessageManager()->addNotice(
                            $this->prepareNotificationMessage($message),
                            self::GLOBAL_MESSAGES_GROUP
                        );
                        break;
                }
            }
        }
    }

    protected function prepareNotificationMessage(array $message)
    {
        if (!empty($message['title'])) {
            return "<strong>{$this->__($message['title'])}</strong><br/>{$this->__($message['text'])}";
        }
        return $this->__($message['text']);
    }

    // ---------------------------------------

    protected function addServerMaintenanceInfo()
    {
        $helper = $this->helperFactory->getObject('Server_Maintenance');

        if ($helper->isNow()) {
            $message = 'M2E Pro Server is under maintenance. It is scheduled to last';
            $message .= ' %from% to %to%.Please do not apply Product Actions (List, Relist, Revise, Stop)';
            $message .= ' during this time frame.';

            $this->getMessageManager()->addNotice(
                $this->__(
                    $message,
                    $helper->getDateEnabledFrom()->format('Y-m-d H:i:s'),
                    $helper->getDateEnabledTo()->format('Y-m-d H:i:s')
                )
            );
        } elseif ($helper->isScheduled()) {
            $message = 'M2E Pro Server maintenance is scheduled. The Service will be unavailable';
            $message .= ' %from% to %to%. Product updates will be processed after the technical works are finished.';

            $this->getMessageManager()->addWarning(
                $this->__(
                    $message,
                    $helper->getDateEnabledFrom()->format('Y-m-d H:i:s'),
                    $helper->getDateEnabledTo()->format('Y-m-d H:i:s')
                )
            );
        }
    }

    // ---------------------------------------

    protected function addCronErrorMessage()
    {
        if (!$this->getHelper('Module_Cron')->isModeEnabled()) {
            return $this->getMessageManager()->addWarning(
                'Automatic Synchronization is disabled. You can enable it under <i>Stores > Settings > Configuration
                    > M2E Pro > Advanced Settings > Automatic Synchronization</i>.',
                \Ess\M2ePro\Controller\Adminhtml\Base::GLOBAL_MESSAGES_GROUP
            );
        }

        if ($this->getHelper('Module')->isReadyToWork() &&
            $this->getHelper('Module\Cron')->isLastRunMoreThan(1, true) &&
            !$this->getHelper('Module')->isDevelopmentEnvironment()
        ) {
            $url = $this->getHelper('Module\Support')->getKnowledgebaseArticleUrl('cron-running');

            $message  = 'Attention! AUTOMATIC Synchronization is not running at the moment.';
            $message .= ' It does not allow M2E Pro to work correctly.';
            $message .= '<br/>Please check this <a href="%url%" target="_blank" class="external-link">article</a>';
            $message .= ' for the details on how to resolve the problem.';
            $message = $this->getHelper('Module\Translation')->__($message, $url);

            $this->getMessageManager()->addError(
                $message,
                \Ess\M2ePro\Controller\Adminhtml\Base::GLOBAL_MESSAGES_GROUP
            );
        }
    }

    // ---------------------------------------

    protected function addLicenseActivationNotifications()
    {
        /** @var License $licenseHelper */
        $licenseHelper = $this->getHelper('Module\License');

        if (!$licenseHelper->getKey() || !$licenseHelper->getDomain() || !$licenseHelper->getIp()) {
            $params = [];
            $this->isContentLockedByWizard() && $params['wizard'] = '1';
            $url = $this->getHelper('View\Configuration')->getLicenseUrl($params);

            $message = $this->__(
                'M2E Pro Module requires activation. Go to the <a href="%url%" target ="_blank">License Page</a>.',
                $url
            );

            $this->getMessageManager()->addError($message, self::GLOBAL_MESSAGES_GROUP);
            return true;
        }

        return false;
    }

    protected function addLicenseValidationFailNotifications()
    {
        /** @var License $licenseHelper */
        $licenseHelper = $this->getHelper('Module\License');

        if (!$licenseHelper->isValidDomain()) {
            $params = [];
            if ($this->getHelper('Module\Wizard')->getActiveBlockerWizard($this->getCustomViewNick())) {
                $params['wizard'] = '1';
            }
            $url = $this->getHelper('View\Configuration')->getLicenseUrl($params);

            $message = 'M2E Pro License Key Validation is failed for this Domain. ';
            $message .= 'Go to the <a href="%url%" target="_blank">License Page</a>.';
            $message = $this->__($message, $url);

            $this->getMessageManager()->addError($message, self::GLOBAL_MESSAGES_GROUP);

            return true;
        }

        if (!$licenseHelper->isValidIp()) {
            $params = [];
            if ($this->getHelper('Module\Wizard')->getActiveBlockerWizard($this->getCustomViewNick())) {
                $params['wizard'] = '1';
            }
            $url = $this->getHelper('View\Configuration')->getLicenseUrl($params);

            $message = 'M2E Pro License Key Validation is failed for this IP. ';
            $message .= 'Go to the <a href="%url%" target="_blank">License Page</a>.';
            $message = $this->__($message, $url);

            $this->getMessageManager()->addError($message, self::GLOBAL_MESSAGES_GROUP);

            return true;
        }

        return false;
    }

    protected function addLicenseStatusNotifications()
    {
        /** @var License $licenseHelper */
        $licenseHelper = $this->getHelper('Module\License');

        if (!$licenseHelper->getStatus()) {
            $params = [];
            $this->isContentLockedByWizard() && $params['wizard'] = '1';
            $url = $this->getHelper('View\Configuration')->getLicenseUrl($params);

            $message = $this->__(
                'The License is suspended. Go to the <a href="%url%" target ="_blank">License Page</a>.',
                $url
            );

            $this->getMessageManager()->addError($message, self::GLOBAL_MESSAGES_GROUP);

            return true;
        }

        return false;
    }

    protected function addStaticContentWarningNotification()
    {
        if (!$this->getHelper('Magento')->isProduction()) {
            return false;
        }

        $skipMessageForVersion = $this->getHelper('Module')->getRegistry()->getValue(
            '/global/notification/static_content/skip_for_version/'
        );

        if (version_compare($skipMessageForVersion, $this->getHelper('Module')->getPublicVersion(), '==')) {
            return false;
        }

        $deployDate = $this->getHelper('Magento')->getLastStaticContentDeployDate();
        if (!$deployDate) {
            return false;
        }

        $setupResource = $this->activeRecordFactory->getObject('Setup')->getResource();
        $lastUpgradeDate = $setupResource->getLastUpgradeDate();
        if (!$lastUpgradeDate) {
            return false;
        }

        $lastUpgradeDate = new \DateTime($lastUpgradeDate, new \DateTimeZone('UTC'));
        $deployDate = new \DateTime($deployDate, new \DateTimeZone('UTC'));

        if ($deployDate->getTimestamp() > $lastUpgradeDate->modify('- 30 minutes')->getTimestamp()) {
            return false;
        }

        $skipMessageUrl = $this->getUrl(
            '*/general/skipStaticContentValidationMessage',
            [
                'skip_message' => true,
                'back' => base64_encode($this->getUrl('*/*/*', ['_current' => true]))
            ]
        );

        $docsUrl = 'https://devdocs.magento.com/guides/v2.3/config-guide/cli/config-cli-subcommands-static-view.html';
        $this->addExtendedWarningMessage(
            $this->__(
                '<p>Static content data was not deployed during the last M2E Pro installation/upgrade.
                 It may affect some elements of your Magento user interface.</p>
                 <p>Please follow <a href="%url_1%" target="_blank">these instructions</a>
                 to deploy static view files.</p>

                 <a href="%url_2%">Don\'t Show Again</a><br>',
                $docsUrl,
                $skipMessageUrl
            ),
            self::GLOBAL_MESSAGES_GROUP
        );

        return true;
    }

    //########################################

    private function isContentLocked()
    {
        return $this->getHelper('Magento')->isProduction() &&
               !$this->getHelper('Module')->isStaticContentDeployed();
    }

    private function isContentLockedByWizard()
    {
        if ($this->isAjax()) {
            return false;
        }

        /** @var Module\Wizard $wizardHelper */
        $wizardHelper = $this->getHelper('Module\Wizard');

        if (!($activeWizard = $wizardHelper->getActiveBlockerWizard($this->getCustomViewNick()))) {
            return false;
        }

        $activeWizardNick = $wizardHelper->getNick($activeWizard);

        if ((bool)$this->getRequest()->getParam('wizard', false) ||
            $this->getRequest()->getControllerName() == 'wizard_'.$activeWizardNick) {
            return false;
        }

        return true;
    }

    //########################################
}

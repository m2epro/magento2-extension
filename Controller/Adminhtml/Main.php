<?php

namespace Ess\M2ePro\Controller\Adminhtml;

use Ess\M2ePro\Helper\Module;
use Ess\M2ePro\Helper\Module\License;
use Ess\M2ePro\Helper\Module\Maintenance;
use Ess\M2ePro\Model\Servicing\Dispatcher;
use Ess\M2ePro\Model\HealthStatus\Task\Result;

abstract class Main extends Base
{
    private $systemRequirementsChecked = false;

    //########################################

    protected function preDispatch(\Magento\Framework\App\RequestInterface $request)
    {
        if (($preDispatchResult = parent::preDispatch($request)) !== true) {
            return $preDispatchResult;
        }

        if ($this->getCustomViewNick() && empty($this->getCustomViewComponentHelper()->getEnabledComponents())) {
            return $this->_redirect('admin/dashboard');
        }

        $blockerWizardNick = $this->getBlockerWizardNick();
        if ($blockerWizardNick !== false) {
            return $this->_redirect('*/wizard_' . $blockerWizardNick);
        }

        $this->addNotificationMessages();

        if ($request->isGet() && !$request->isPost() && !$request->isXmlHttpRequest()) {

            try {
                $this->getHelper('Client')->updateBackupConnectionData(false);
            } catch (\Exception $exception) {}

            try {

                /** @var Dispatcher $dispatcher */
                $dispatcher = $this->modelFactory->getObject('Servicing\Dispatcher');
                $dispatcher->process(Dispatcher::DEFAULT_INTERVAL, $dispatcher->getFastTasks());

            } catch (\Exception $exception) {}
        }

        /** @var Maintenance\Debug $maintenanceHelper */
        $maintenanceHelper = $this->getHelper('Module\Maintenance\Debug');

        if ($maintenanceHelper->isEnabled()) {

            if ($maintenanceHelper->isOwner()) {
                $maintenanceHelper->prolongRestoreDate();
            } elseif ($maintenanceHelper->isExpired()) {
                $maintenanceHelper->disable();
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

        return parent::addContent($block);
    }

    // ---------------------------------------

    protected function beforeAddContentEvent()
    {
        $this->appendRequirementsPopup();
        parent::beforeAddContentEvent();
    }

    protected function appendRequirementsPopup()
    {
        if ($this->systemRequirementsChecked) {
            return;
        }

        if ($this->getHelper('Module')->getCacheConfig()->getGroupValue('/view/requirements/popup/', 'closed')) {
            return;
        };

        $isMeetRequirements = $this->getHelper('Data\Cache\Permanent')->getValue('is_meet_requirements');

        if (is_null($isMeetRequirements)) {

            $isMeetRequirements = true;
            foreach ($this->getHelper('Module')->getRequirementsInfo() as $requirement) {
                if (!$requirement['current']['status']) {
                    $isMeetRequirements = false;
                    break;
                }
            }

            $this->getHelper('Data\Cache\Permanent')->setValue(
                'is_meet_requirements', (int)$isMeetRequirements, array(), 60*60
            );
        }

        if ($isMeetRequirements) {
            return;
        }

        $block = $this->createBlock('RequirementsPopup');
        $this->getLayout()->setChild('js', $block->getNameInLayout(), '');

        $this->systemRequirementsChecked = true;
    }

    //########################################

    protected function getCustomViewHelper()
    {
        return $this->getHelper('View')->getViewHelper($this->getCustomViewNick());
    }

    protected function getCustomViewComponentHelper()
    {
        return $this->getHelper('View')->getComponentHelper($this->getCustomViewNick());
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
            !$this->getRequest()->isXmlHttpRequest()) {

            $staticNotification = $this->addStaticContentNotification();
            $browserNotification = $this->addBrowserNotifications();
            $maintenanceNotification = $this->addMaintenanceNotifications();
            $healthStatusNotifications = $this->addHealthStatusNotifications();

            $muteMessages = $staticNotification || $browserNotification ||
                            $maintenanceNotification || $healthStatusNotifications;

            if (!$muteMessages && $this->getCustomViewHelper()->isInstallationWizardFinished()) {
                $this->addLicenseNotifications();
            }

            if (!$muteMessages) {
                $this->addStaticContentWarningNotification();
            }

            $this->addServerNotifications();

            if (!$muteMessages) {
                $this->addCronErrorMessage();
                $this->getCustomViewControllerHelper()->addMessages($this);
            }
        }
    }

    // ---------------------------------------

    protected function addBrowserNotifications()
    {
// M2ePro_TRANSLATIONS
// We are sorry, Internet Explorer browser is not supported. Please, use another browser (Mozilla Firefox, Google Chrome, etc.).
        if ($this->getHelper('Client')->isBrowserIE()) {
            $this->getMessageManager()->addError($this->__(
                'We are sorry, Internet Explorer browser is not supported. Please, use'.
                ' another browser (Mozilla Firefox, Google Chrome, etc.).'
            ), self::GLOBAL_MESSAGES_GROUP);
            return true;
        }
        return false;
    }

    protected function addMaintenanceNotifications()
    {
        if (!$this->getHelper('Module\Maintenance\Debug')->isEnabled()) {
            return false;
        }

        if ($this->getHelper('Module\Maintenance\Debug')->isOwner()) {

            $this->getMessageManager()->addNotice($this->__(
                'Maintenance is Active.'
            ), self::GLOBAL_MESSAGES_GROUP);

            return false;
        }

        $this->getMessageManager()->addError($this->__(
                'M2E Pro is working in Maintenance Mode at the moment. Developers are investigating your issue.'
            ).'<br/>'.$this->__(
                'You will be able to see a content of this Page soon.
                 Please wait and then refresh a browser Page later.'
            ), self::GLOBAL_MESSAGES_GROUP);

        return true;
    }

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
        $notificationSettings = $this->modelFactory->getObject('HealthStatus\Notification\Settings');

        if (!$notificationSettings->isModeExtensionPages()) {
            return false;
        }

        if ($currentStatus->get() < $notificationSettings->getLevel()) {
            return false;
        }

        $messageBuilder = $this->modelFactory->getObject('HealthStatus\Notification\MessageBuilder');

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
        $this->addLicenseActivationNotifications() ||
        $this->addLicenseValidationFailNotifications() ||
        $this->addLicenseStatusNotifications();
    }

    // ---------------------------------------

    protected function addServerNotifications()
    {
        $messages = $this->getHelper('Module')->getServerMessages();

        foreach ($messages as $message) {

            if (isset($message['text']) && isset($message['type']) && $message['text'] != '') {

                switch ($message['type']) {
                    case \Ess\M2ePro\Helper\Module::SERVER_MESSAGE_TYPE_ERROR:
                        $this->getMessageManager()->addError(
                            $this->prepareServerNotificationMessage($message),
                            self::GLOBAL_MESSAGES_GROUP
                        );
                        break;
                    case \Ess\M2ePro\Helper\Module::SERVER_MESSAGE_TYPE_WARNING:
                        $this->getMessageManager()->addWarning(
                            $this->prepareServerNotificationMessage($message),
                            self::GLOBAL_MESSAGES_GROUP
                        );
                        break;
                    case \Ess\M2ePro\Helper\Module::SERVER_MESSAGE_TYPE_SUCCESS:
                        $this->getMessageManager()->addSuccess(
                            $this->prepareServerNotificationMessage($message),
                            self::GLOBAL_MESSAGES_GROUP
                        );
                        break;
                    case \Ess\M2ePro\Helper\Module::SERVER_MESSAGE_TYPE_NOTICE:
                    default:
                        $this->getMessageManager()->addNotice(
                            $this->prepareServerNotificationMessage($message),
                            self::GLOBAL_MESSAGES_GROUP
                        );
                        break;
                }
            }
        }
    }

    protected function prepareServerNotificationMessage(array $message)
    {
        if ($message['title']) {
            return "<strong>{$this->__($message['title'])}</strong><br/>{$this->__($message['text'])}";
        }
        return $this->__($message['text']);
    }

    // ---------------------------------------

    protected function addCronErrorMessage()
    {
        if ($this->getHelper('Module')->isReadyToWork() &&
            $this->getHelper('Module\Cron')->isLastRunMoreThan(1, true) &&
            !$this->getHelper('Module')->isDevelopmentEnvironment()) {

            $url = $this->getHelper('Module\Support')->getKnowledgebaseArticleUrl(
                '692955-why-cron-service-is-not-working-in-my-magento'
            );

            // M2ePro_TRANSLATIONS
            // Attention! AUTOMATIC Synchronization is not running at the moment. It does not allow M2E Pro to work correctly.<br/>Please check this <a href="%url% target="_blank" class="external-link">article</a> for the details on how to resolve the problem.
            $message  = 'Attention! AUTOMATIC Synchronization is not running at the moment.';
            $message .= ' It does not allow M2E Pro to work correctly.';
            $message .= '<br/>Please check this <a href="%url%" target="_blank" class="external-link">article</a>';
            $message .= ' for the details on how to resolve the problem.';
            $message = $this->getHelper('Module\Translation')->__($message, $url);

            $this->getMessageManager()->addError(
                $message, \Ess\M2ePro\Controller\Adminhtml\Base::GLOBAL_MESSAGES_GROUP
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
            $this->getBlockerWizardNick() && $params['wizard'] = '1';
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
            $this->getBlockerWizardNick() && $params['wizard'] = '1';
            $url = $this->getHelper('View\Configuration')->getLicenseUrl($params);

// M2ePro_TRANSLATIONS
// M2E Pro License Key Validation is failed for this Domain. Go to the <a href="%url%" target="_blank">License Page</a>.
            $message = 'M2E Pro License Key Validation is failed for this Domain. ';
            $message .= 'Go to the <a href="%url%" target="_blank">License Page</a>.';
            $message = $this->__($message,$url);

            $this->getMessageManager()->addError($message, self::GLOBAL_MESSAGES_GROUP);

            return true;
        }

        if (!$licenseHelper->isValidIp()) {

            $params = [];
            $this->getBlockerWizardNick() && $params['wizard'] = '1';
            $url = $this->getHelper('View\Configuration')->getLicenseUrl($params);

// M2ePro_TRANSLATIONS
// M2E Pro License Key Validation is failed for this IP. Go to the <a href="%url%" target="_blank">License Page</a>.
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
            $this->getBlockerWizardNick() && $params['wizard'] = '1';
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
            return;
        }

        $skipMessageForVersion = $this->modelFactory->getObject('Config\Manager\Cache')->getGroupValue(
            '/global/notification/message/', 'skip_static_content_validation_message'
        );

        if (version_compare($skipMessageForVersion, $this->getHelper('Module')->getPublicVersion(), '==')) {
            return;
        }

        $deployDate = $this->getHelper('Magento')->getLastStaticContentDeployDate();
        if (!$deployDate) {
            return;
        }

        $lastModificationDate = $this->getHelper('Module')->getPublicVersionLastModificationDate();
        if (empty($lastModificationDate)) {
            return;
        }

        $lastModificationDate = new \DateTime($lastModificationDate, new \DateTimeZone('UTC'));
        $deployDate = new \DateTime($deployDate, new \DateTimeZone('UTC'));

        // we are reducing some safe interval
        if ($deployDate > $lastModificationDate->modify('- 30 minutes')) {
            return;
        }

        $url = $this->getUrl('*/general/skipStaticContentValidationMessage',
            [
                'skip_message' => true,
                'back' => base64_encode($this->getUrl('*/*/*', ['_current' => true]))
            ]
        );

        $this->addExtendedWarningMessage(
            $this->__(
                '<p>The static content data was not deployed from the latest upgrade of M2E Pro Extension.
                 It causes the incorrect working of all or some part of the Interface.</p>
                 <p>Thus, to solve this issue you should follow the recommendations provided in this
                 <a href="%url_1%" target="_blank">article</a> and update the static content data.</p>

                 <a href="%url_2%">Don\'t Show Again</a><br>',
                $this->getHelper('Module\Support')->getDocumentationArticleUrl('x/ZgM0AQ'),
                $url
            ),
            self::GLOBAL_MESSAGES_GROUP
        );
    }

    //########################################

    private function isContentLocked()
    {
        return $this->getHelper('Client')->isBrowserIE()
                || (
                       $this->getHelper('Magento')->isProduction() &&
                       !$this->getHelper('Module')->isStaticContentDeployed()
                   )
                || (
                       $this->getHelper('Module\Maintenance\Debug')->isEnabled() &&
                       !$this->getHelper('Module\Maintenance\Debug')->isOwner()
                   );
    }

    private function getBlockerWizardNick()
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

        return $activeWizardNick;
    }

    //########################################
}
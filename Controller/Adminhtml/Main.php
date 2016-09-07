<?php

namespace Ess\M2ePro\Controller\Adminhtml;

use Ess\M2ePro\Helper\Module;
use Ess\M2ePro\Helper\Module\License;
use Ess\M2ePro\Helper\Module\Maintenance;
use Ess\M2ePro\Model\Servicing\Dispatcher;
use Magento\Backend\App\Action;

abstract class Main extends Base
{
    //########################################

    protected function preDispatch(\Magento\Framework\App\RequestInterface $request)
    {
        $result = parent::preDispatch($request);

        if ($this->getHelper('Module\Maintenance\General')->isEnabled()) {
            return $this->_redirect('*/maintenance');
        }

        if (empty($this->getCustomViewComponentHelper()->getEnabledComponents())) {
            return $this->_redirect('admin/dashboard');
        }

        $blockerWizardNick = $this->getBlockerWizardNick();
        if ($blockerWizardNick !== false) {
            $this->_redirect('*/wizard_' . $blockerWizardNick);
            return false;
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

        return $result;
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

            $muteMessages = $staticNotification || $browserNotification || $maintenanceNotification;

            if (!$muteMessages && $this->getCustomViewHelper()->isInstallationWizardFinished()) {
                $this->addLicenseNotifications();
            }

            if (!$muteMessages) {
                $this->addStaticContentWarningNotification();
            }
            
            $this->addServerNotifications();

            if (!$muteMessages) {
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
            $this->getMessageManager()->addErrorMessage(
                $this->__('Run "setup:static-content:deploy" TODO TEXT'),
                self::GLOBAL_MESSAGES_GROUP
            );

            return true;
        }
        
        return false;
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

    protected function addLicenseActivationNotifications()
    {
        /** @var License $licenseHelper */
        $licenseHelper = $this->getHelper('Module\License');

        if (!$licenseHelper->getKey() || !$licenseHelper->getDomain() || !$licenseHelper->getIp()) {

            $url = $this->getHelper('View\Configuration')->getLicenseUrl();

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

            $url = $this->getHelper('View\Configuration')->getLicenseUrl();

// M2ePro_TRANSLATIONS
// M2E Pro License Key Validation is failed for this Domain. Go to the <a href="%url%" target="_blank">License Page</a>.
            $message = 'M2E Pro License Key Validation is failed for this Domain. ';
            $message .= 'Go to the <a href="%url%" target="_blank">License Page</a>.';
            $message = $this->__($message,$url);

            $this->getMessageManager()->addError($message, self::GLOBAL_MESSAGES_GROUP);

            return true;
        }

        if (!$licenseHelper->isValidIp()) {

            $url = $this->getHelper('View\Configuration')->getLicenseUrl();

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

            $url = $this->getHelper('View\Configuration')->getLicenseUrl();

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

        if (version_compare($skipMessageForVersion, $this->getHelper('Module')->getVersion(), '==')) {
            return;
        }

        $deployDate = $this->getHelper('Magento')->getLastStaticContentDeployDate();
        if (!$deployDate) {
            return;
        }
        
        $lastDbModificationDate = $this->getHelper('Module')->getLastUpgradeDate();
        if (empty($lastDbModificationDate)) {
            $lastDbModificationDate = $this->getHelper('Module')->getInstallationDate();
        }

        if (empty($lastDbModificationDate)) {
            return;
        }

        $lastDbModificationDate = new \DateTime($lastDbModificationDate, new \DateTimeZone('UTC'));
        $deployDate = new \DateTime($deployDate, new \DateTimeZone('UTC'));

        /** We check only database version because we can't retrieve date of update our module from composer */
        if ($deployDate > $lastDbModificationDate) {
            return;
        }

        $url = $this->getUrl('*/general/skipStaticContentValidationMessage',
            ['skip_message' => true, 'back' => base64_encode($this->getUrl('*/*/*'))]
        );

        $this->addExtendedWarningMessage(
            $this->__(
                'Run "setup:static-content:deploy" TODO TEXT 
                    <a href="'.$url.'" class="skippable-global-message">Don\'t Show Again</a>'
            ),
            self::GLOBAL_MESSAGES_GROUP
        );
    }

    //########################################

    private function isContentLocked()
    {
        return $this->getHelper('Module\Maintenance\General')->isEnabled()
                || $this->getHelper('Client')->isBrowserIE()
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
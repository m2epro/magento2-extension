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
        parent::preDispatch($request);

        $blockerWizardNick = $this->getBlockerWizardNick();
        if ($blockerWizardNick !== false) {
            $this->_redirect('*/wizard_' . $blockerWizardNick);
            return;
        }

        $this->addNotificationMessages();

        if ($request->isGet() && !$request->isPost() && !$request->isXmlHttpRequest()) {
            if (empty($this->getCustomViewComponentHelper()->getEnabledComponents())) {
                throw new \Ess\M2ePro\Model\Exception('At least 1 channel of current View should be enabled.');
            }

            try {
                $this->getHelper('Client')->updateBackupConnectionData(false);
            } catch (\Exception $exception) {}

            try {

                /** @var Dispatcher $dispatcher */
                $dispatcher = $this->modelFactory->getObject('Servicing\Dispatcher');
                $dispatcher->process(Dispatcher::DEFAULT_INTERVAL, $dispatcher->getFastTasks());

            } catch (\Exception $exception) {}
        }

        /** @var Maintenance\Developer $maintenanceHelper */
        $maintenanceHelper = $this->getHelper('Module\Maintenance\Developer');

        if ($maintenanceHelper->isEnabled()) {

            if ($maintenanceHelper->isOwner()) {
                $maintenanceHelper->prolongRestoreDate();
            } elseif ($maintenanceHelper->isExpired()) {
                $maintenanceHelper->disable();
            }
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

            $browserNotification = $this->addBrowserNotifications();
            $maintenanceNotification = $this->addMaintenanceNotifications();

            $muteMessages = $browserNotification || $maintenanceNotification;

            if (!$muteMessages && $this->getCustomViewHelper()->isInstallationWizardFinished()) {
                $this->addLicenseNotifications();
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
        if (!$this->getHelper('Module\Maintenance\Developer')->isEnabled()) {
            return false;
        }

        if ($this->getHelper('Module\Maintenance\Developer')->isOwner()) {

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

    protected function addLicenseNotifications()
    {
        $this->addLicenseActivationNotifications() ||
        $this->addLicenseValidationFailNotifications() ||
        $this->addLicenseStatusNotifications();
    }

    protected function addServerNotifications()
    {
        $messages = $this->getHelper('Module')->getServerMessages();

        foreach ($messages as $message) {

            if (isset($message['text']) && isset($message['type']) && $message['text'] != '') {

                switch ($message['type']) {
                    case \Ess\M2ePro\Helper\Module::SERVER_MESSAGE_TYPE_ERROR:
                        $this->getMessageManager()->addError($this->__($message['text']));
                        break;
                    case \Ess\M2ePro\Helper\Module::SERVER_MESSAGE_TYPE_WARNING:
                        $this->getMessageManager()->addWarning($this->__($message['text']));
                        break;
                    case \Ess\M2ePro\Helper\Module::SERVER_MESSAGE_TYPE_SUCCESS:
                        $this->getMessageManager()->addSuccess($this->__($message['text']));
                        break;
                    case \Ess\M2ePro\Helper\Module::SERVER_MESSAGE_TYPE_NOTICE:
                    default:
                        $this->getMessageManager()->addNotice($this->__($message['text']));
                        break;
                }
            }
        }
    }

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

    //########################################

    private function isContentLocked()
    {
        return $this->getHelper('Module\Maintenance\Setup')->isEnabled() || $this->getHelper('Client')->isBrowserIE() ||
                (
                    $this->getHelper('Module\Maintenance\Developer')->isEnabled() &&
                    !$this->getHelper('Module\Maintenance\Developer')->isOwner()
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
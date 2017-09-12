<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon;

class GetGlobalMessages extends Main
{
    //########################################

    public function execute()
    {
        $browserNotification = $this->addBrowserNotifications();
        $maintenanceNotification = $this->addMaintenanceNotifications();

        $muteMessages = $browserNotification || $maintenanceNotification;

        if (!$muteMessages && $this->getCustomViewHelper()->isInstallationWizardFinished()) {
            $this->addLicenseNotifications();
        }

        $this->addServerNotifications();

        if (!$muteMessages) {
            $this->addCronErrorMessage();
            $this->getCustomViewControllerHelper()->addMessages($this);
        }

        $messages = $this->getMessageManager()->getMessages(
            true, \Ess\M2ePro\Controller\Adminhtml\Base::GLOBAL_MESSAGES_GROUP
        )->getItems();

        foreach ($messages as &$message) {
            $message = [$message->getType() => $message->getText()];
        }

        $this->setJsonContent($messages);
        return $this->getResult();
    }

    //########################################
}
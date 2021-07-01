<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Servicing\Task;

use Ess\M2ePro\Model\Issue\DataObject as Issue;

/**
 * Class \Ess\M2ePro\Model\Servicing\Task\Messages
 */
class Messages extends \Ess\M2ePro\Model\Servicing\Task
{
    //########################################

    /**
     * @return string
     */
    public function getPublicNick()
    {
        return 'messages';
    }

    //########################################

    /**
     * @return array
     */
    public function getRequestData()
    {
        return [];
    }

    /**
     * @param array $data
     *
     * @return void
     * @throws \Exception
     */
    public function processResponseData(array $data)
    {
        $this->updateMagentoMessages($data);
        $this->updateModuleMessages($data);
        $this->updateUpgradeMessages();
    }

    //########################################

    private function updateMagentoMessages(array $messages)
    {
        $messages = array_filter($messages, function ($message) {
            return isset($message['is_global']) && (bool)$message['is_global'];
        });

        /** @var \Ess\M2ePro\Model\Issue\Notification\Channel\Magento\GlobalMessage $notificationChannel */
        $notificationChannel = $this->modelFactory->getObject('Issue_Notification_Channel_Magento_GlobalMessage');

        foreach ($messages as $messageData) {
            /** @var \Ess\M2ePro\Model\Issue\DataObject $issue */
            $issue = $this->modelFactory->getObject('Issue_DataObject', [
                Issue::KEY_TYPE  => (int)$messageData['type'],
                Issue::KEY_TITLE => isset($messageData['title']) ? $messageData['title'] : 'M2E Pro Notification',
                Issue::KEY_TEXT  => isset($messageData['text']) ? $messageData['text'] : null,
                Issue::KEY_URL   => isset($messageData['url']) ? $messageData['url'] : null
            ]);
            $notificationChannel->addMessage($issue);
        }
    }

    //########################################

    private function updateModuleMessages(array $messages)
    {
        $messages = array_filter($messages, function ($message) {
            return !isset($message['is_global']) || !(bool)$message['is_global'];
        });

        $this->getHelper('Module')->getRegistry()->setValue('/server/messages/', $messages);
    }

    //########################################

    /**
     * @throws \Exception
     */
    private function updateUpgradeMessages()
    {
        $messages = $this->getHelper('Module')->getRegistry()->getValueFromJson('/upgrade/messages/');
        if (empty($messages)) {
            return;
        }

        $nowDateTime = new \DateTime('now', new \DateTimeZone('UTC'));
        $messages = array_filter($messages, function ($message) use ($nowDateTime) {
            if (!isset($message['lifetime'])) {
                return false;
            }

            $toDateTime = new \DateTime($message['lifetime'], new \DateTimeZone('UTC'));
            if ($nowDateTime->getTimestamp() > $toDateTime->getTimestamp()) {
                return false;
            }

            return true;
        });

        $this->getHelper('Module')->getRegistry()->setValue('/upgrade/messages/', $messages);
    }

    //########################################
}

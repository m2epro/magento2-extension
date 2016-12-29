<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Servicing\Task;

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
        return array();
    }

    public function processResponseData(array $data)
    {
        $this->updateMagentoMessages($data);
        $this->updateModuleMessages($data);
    }

    //########################################

    private function updateMagentoMessages(array $messages)
    {
        $messages = array_filter($messages, function ($message) {
            return isset($message['is_global']) && (bool)$message['is_global'];
        });

        $magentoTypes = array(
            \Ess\M2ePro\Helper\Module::SERVER_MESSAGE_TYPE_NOTICE =>
                \Magento\Framework\Notification\MessageInterface::SEVERITY_NOTICE,
            \Ess\M2ePro\Helper\Module::SERVER_MESSAGE_TYPE_SUCCESS =>
                \Magento\Framework\Notification\MessageInterface::SEVERITY_NOTICE,
            \Ess\M2ePro\Helper\Module::SERVER_MESSAGE_TYPE_WARNING =>
                \Magento\Framework\Notification\MessageInterface::SEVERITY_MINOR,
            \Ess\M2ePro\Helper\Module::SERVER_MESSAGE_TYPE_ERROR =>
                \Magento\Framework\Notification\MessageInterface::SEVERITY_CRITICAL
        );

        foreach ($messages as $message) {
            $this->getHelper('Magento')->addGlobalNotification(
                $message['title'],
                $message['text'],
                $magentoTypes[$message['type']]
            );
        }
    }

    //########################################

    private function updateModuleMessages(array $messages)
    {
        $messages = array_filter($messages, function ($message) {
            return !isset($message['is_global']) || !(bool)$message['is_global'];
        });

        /** @var \Ess\M2ePro\Model\Registry $registryModel */
        $registryModel = $this->activeRecordFactory->getObjectLoaded('Registry', '/server/messages/', 'key', false);

        if (is_null($registryModel)) {
            $registryModel = $this->activeRecordFactory->getObject('Registry');
            $registryModel->setData('key', '/server/messages/');
        }

        $registryModel->setData(
            'value', $this->getHelper('Data')->jsonEncode($messages)
        )->save();
    }

    //########################################
}
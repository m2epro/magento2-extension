<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Servicing\Task;

use Ess\M2ePro\Model\Issue\DataObject as Issue;

class Messages implements \Ess\M2ePro\Model\Servicing\TaskInterface
{
    public const NAME = 'messages';

    /** @var \Ess\M2ePro\Model\Registry\Manager */
    private $registryManager;
    /** @var \Ess\M2ePro\Model\Issue\Notification\Channel\Magento\GlobalMessage */
    private $globalMessage;
    /** @var \Ess\M2ePro\Model\Issue\DataObjectFactory */
    private $dataObjectFactory;

    /**
     * @param \Ess\M2ePro\Model\Registry\Manager $registryManager
     * @param \Ess\M2ePro\Model\Issue\Notification\Channel\Magento\GlobalMessage $globalMessage
     * @param \Ess\M2ePro\Model\Issue\DataObjectFactory $dataObjectFactory
     */
    public function __construct(
        \Ess\M2ePro\Model\Registry\Manager $registryManager,
        \Ess\M2ePro\Model\Issue\Notification\Channel\Magento\GlobalMessage $globalMessage,
        \Ess\M2ePro\Model\Issue\DataObjectFactory $dataObjectFactory
    ) {
        $this->registryManager = $registryManager;
        $this->globalMessage = $globalMessage;
        $this->dataObjectFactory = $dataObjectFactory;
    }

    // ----------------------------------------

    /**
     * @return string
     */
    public function getServerTaskName(): string
    {
        return self::NAME;
    }

    // ----------------------------------------

    /**
     * @return bool
     */
    public function isAllowed(): bool
    {
        return true;
    }

    // ----------------------------------------

    /**
     * @return array
     */
    public function getRequestData(): array
    {
        return [];
    }

    /**
     * @param array $data
     *
     * @return void
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Exception
     */
    public function processResponseData(array $data): void
    {
        $this->updateMagentoMessages($data);
        $this->updateModuleMessages($data);
        $this->updateUpgradeMessages();
    }

    // ----------------------------------------

    /**
     * @param array $messages
     *
     * @return void
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    private function updateMagentoMessages(array $messages): void
    {
        $messages = array_filter($messages, function ($message) {
            return isset($message['is_global']) && (bool)$message['is_global'];
        });

        foreach ($messages as $messageData) {
            /** @var \Ess\M2ePro\Model\Issue\DataObject $issue */
            $issue = $this->dataObjectFactory->create([
                Issue::KEY_TYPE  => (int)$messageData['type'],
                Issue::KEY_TITLE => $messageData['title'] ?? 'M2E Pro Notification',
                Issue::KEY_TEXT  => $messageData['text'] ?? null,
                Issue::KEY_URL   => $messageData['url'] ?? null,
            ]);

            $this->globalMessage->addMessage($issue);
        }
    }

    // ----------------------------------------

    /**
     * @param array $messages
     *
     * @return void
     */
    private function updateModuleMessages(array $messages): void
    {
        $messages = array_filter($messages, function ($message) {
            return !isset($message['is_global']) || !(bool)$message['is_global'];
        });

        $this->registryManager->setValue('/server/messages/', $messages);
    }

    // ----------------------------------------

    /**
     * @return void
     * @throws \Exception
     */
    private function updateUpgradeMessages(): void
    {
        $messages = $this->registryManager->getValueFromJson('/upgrade/messages/');
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

        $this->registryManager->setValue('/upgrade/messages/', $messages);
    }
}

<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Connector\Connection\Response;

class MessageFactory
{
    private \Magento\Framework\ObjectManagerInterface $objectManager;

    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function createByResponseData(array $responseData): Message
    {
        $message = $this->create();
        $message->initFromResponseData($responseData);

        return $message;
    }

    public function create(): Message
    {
        return $this->objectManager->create(Message::class);
    }
}

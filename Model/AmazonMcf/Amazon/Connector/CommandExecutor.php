<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\AmazonMcf\Amazon\Connector;

class CommandExecutor
{
    private const MESSAGE_CODE_THROTTLING = 503;
    private const MESSAGE_CODE_AUTHORIZATION = 601;
    private const MESSAGE_CODE_SYSTEM_UNAVAILABLE = 1;

    /**
     * @return \M2E\AmazonMcf\Model\Amazon\Connector\Response\ResponseInterface
     * @throws \M2E\AmazonMcf\Model\Amazon\Connector\Exception\AuthorizationException
     * @throws \M2E\AmazonMcf\Model\Amazon\Connector\Exception\SystemUnavailableException
     * @throws \M2E\AmazonMcf\Model\Amazon\Connector\Exception\ThrottlingException
     */
    public function execute(\Ess\M2ePro\Model\Amazon\Connector\Command\RealTime $command)
    {
        try {
            $command->process();

            $messages = $command->getResponse()->getMessages();
            $this->handleMessages($messages);

            /** @var \M2E\AmazonMcf\Model\Amazon\Connector\Response\ResponseInterface */
            return $command->getResponseData();
        } catch (\Ess\M2ePro\Model\Exception $e) {
            $messages = $command->getResponse()->getMessages();
            if ($messages === null) {
                throw $e;
            }

            /** @see \Ess\M2ePro\Model\Connector\Connection\Single::processRequestResult() */
            $this->handleMessages($messages);

            throw $e;
        }
    }

    /**
     * @throws \M2E\AmazonMcf\Model\Amazon\Connector\Exception\AuthorizationException
     * @throws \M2E\AmazonMcf\Model\Amazon\Connector\Exception\SystemUnavailableException
     * @throws \M2E\AmazonMcf\Model\Amazon\Connector\Exception\ThrottlingException
     */
    private function handleMessages(\Ess\M2ePro\Model\Connector\Connection\Response\Message\Set $messages): void
    {
        foreach ($messages->getErrorEntities() as $message) {
            if ($message->getCode() === self::MESSAGE_CODE_THROTTLING) {
                throw new \M2E\AmazonMcf\Model\Amazon\Connector\Exception\ThrottlingException();
            }

            if ($message->getCode() === self::MESSAGE_CODE_AUTHORIZATION) {
                throw new \M2E\AmazonMcf\Model\Amazon\Connector\Exception\AuthorizationException();
            }

            if ($message->getCode() === self::MESSAGE_CODE_SYSTEM_UNAVAILABLE) {
                throw new \M2E\AmazonMcf\Model\Amazon\Connector\Exception\SystemUnavailableException();
            }
        }
    }
}

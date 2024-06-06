<?php

namespace Ess\M2ePro\Model\AmazonMcf\Amazon\Connector;

trait MessagesTrait
{
    /**
     * @return \M2E\AmazonMcf\Model\Amazon\Connector\Message\Message[]
     */
    private function retrieveMcfMessages(
        \Ess\M2ePro\Model\Amazon\Connector\Command\RealTime $command
    ): array {
        $mcfConnectorMessages = [];
        foreach ($command->getResponse()->getMessages()->getEntities() as $message) {
            if ($message->isError() && $message->isSenderComponent()) {
                $mcfConnectorMessages[] = new \M2E\AmazonMcf\Model\Amazon\Connector\Message\Message(
                    $message->getText()
                );
            }
        }

        return $mcfConnectorMessages;
    }
}
